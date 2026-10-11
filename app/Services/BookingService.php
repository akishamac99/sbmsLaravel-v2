<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Service;
use App\Models\Staff;
use App\Models\SystemLog;
use App\Models\SystemPolicy;
use App\Models\UserNotification;
use DomainException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

// Prices, checkout, payment proof and cancellation.
// Ported from checkout.php, upload_payment.php and SalonSystem (cancelBookingByCustomer, hasUnpaidBookings, ...).
// Every problem the customer can fix is thrown as a DomainException whose message is safe to show on screen.
class BookingService
{
    public const HOME_FEE_NAME = 'Home Service Fee';

    public function __construct(private CartService $cart, private PromoService $promos) {}

    /* ------------------------------------------------------------------
     | Small rules
     ------------------------------------------------------------------ */

    // Only makeup services can be done at the customer's home.
    public static function isMakeup(Service $service): bool
    {
        $service->loadMissing('category');

        foreach ([$service->serviceName, $service->category?->category_name, $service->description] as $text) {
            if (stripos((string) $text, 'makeup') !== false) {
                return true;
            }
        }

        return false;
    }

    public function homeServiceFee(): float
    {
        $price = Service::where('serviceName', self::HOME_FEE_NAME)->where('is_active', 1)->value('servicePrice');

        return $price !== null ? (float) $price : 500.0;
    }

    public function downpaymentPercent(): float
    {
        return (float) SystemPolicy::value('downpayment_percent', 20);
    }

    public static function downpayment(float $total, float $percent): float
    {
        return ($total * $percent) / 100;
    }

    // A customer with an unpaid, still-open booking cannot place a new one.
    public function hasUnpaid(int $customerId): bool
    {
        return Booking::where('customerID', $customerId)
            ->whereNotIn('paymentStatus', ['paid', 'confirmed', 'cancelled'])
            ->whereNotIn('bookingStatus', ['cancelled', 'declined', 'completed'])
            ->exists();
    }

    /* ------------------------------------------------------------------
     | Prices
     ------------------------------------------------------------------ */

    // Share a discount over several bookings, in proportion to what each one costs.
    // The last booking absorbs any rounding cents, so the pieces always add up to the exact totals.
    // Returns one ['discount', 'total', 'downpayment'] row per amount, in the same order.
    public static function allocate(array $grosses, float $discount, float $percent): array
    {
        $grosses = array_values($grosses);
        $subtotal = array_sum($grosses);
        $downpaymentTotal = round(self::downpayment(max(0, $subtotal - $discount), $percent), 2);
        $lastIndex = count($grosses) - 1;

        $rows = [];
        $usedDiscount = 0.0;
        $usedDownpayment = 0.0;

        foreach ($grosses as $i => $gross) {
            $isLast = $i === $lastIndex;

            $itemDiscount = $isLast
                ? round($discount - $usedDiscount, 2)
                : ($subtotal > 0 ? round($discount * $gross / $subtotal, 2) : 0.0);
            $itemDiscount = max(0.0, min($itemDiscount, $gross));

            $itemTotal = round($gross - $itemDiscount, 2);

            $itemDownpayment = $isLast
                ? round($downpaymentTotal - $usedDownpayment, 2)
                : round(self::downpayment($itemTotal, $percent), 2);
            $itemDownpayment = max(0.0, min($itemDownpayment, $itemTotal));

            $usedDiscount += $itemDiscount;
            $usedDownpayment += $itemDownpayment;

            $rows[] = ['discount' => $itemDiscount, 'total' => $itemTotal, 'downpayment' => $itemDownpayment];
        }

        return $rows;
    }

    // Everything the checkout page shows, and everything checkout() saves.
    // $items are cart items, $modes maps cartID => 'salon' | 'home_service'.
    public function quote(Collection $items, array $modes, ?string $promoCode): array
    {
        $items->loadMissing('service.category');

        $fee = $this->homeServiceFee();
        $percent = $this->downpaymentPercent();
        $lines = [];
        $firstHome = null;

        foreach ($items->values() as $i => $item) {
            $requested = $modes[$item->cartID] ?? 'salon';
            $mode = ($requested === 'home_service' && $item->service->is_home_service && self::isMakeup($item->service))
                ? 'home_service'
                : 'salon';

            $lines[$i] = ['item' => $item, 'mode' => $mode, 'price' => (float) $item->service->servicePrice, 'fee' => 0.0];

            if ($mode === 'home_service' && $firstHome === null) {
                $firstHome = $i;
            }
        }

        // The home service fee is charged once, and is carried by the first home-service booking.
        if ($firstHome !== null) {
            $lines[$firstHome]['fee'] = $fee;
        }

        $grosses = array_map(fn ($line) => $line['price'] + $line['fee'], $lines);
        $subtotal = array_sum($grosses);

        $promo = $this->promos->adjustment($promoCode, $subtotal);
        $discount = $promo['valid'] ? $promo['discount'] : 0.0;

        foreach (self::allocate($grosses, $discount, $percent) as $i => $amounts) {
            $lines[$i] += $amounts;
        }

        $total = round(max(0, $subtotal - $discount), 2);

        return [
            'lines' => $lines,
            'subtotal' => round($subtotal, 2),
            'has_home' => $firstHome !== null,
            'home_fee' => $firstHome !== null ? $fee : 0.0,
            'promo_valid' => $promo['valid'],
            'promo' => $promo['promo'],
            'discount' => $discount,
            'discount_reason' => $promo['reason'],
            'total' => $total,
            'percent' => $percent,
            'downpayment' => round(self::downpayment($total, $percent), 2),
        ];
    }

    /* ------------------------------------------------------------------
     | Checkout
     ------------------------------------------------------------------ */

    // Turn the chosen cart items into bookings (one booking per item) with their payment records.
    // $input: payment_type (full|partial), payment_method (gcash|bank_transfer|cash), promo_code, home_address, special_request.
    public function checkout(Customer $customer, Collection $items, array $modes, array $input, ?UploadedFile $proof): Collection
    {
        $method = $input['payment_method'] ?? 'gcash';
        $type = $input['payment_type'] ?? 'partial';
        $address = trim((string) ($input['home_address'] ?? ''));
        $extraRequest = trim((string) ($input['special_request'] ?? ''));
        $promoCode = trim((string) ($input['promo_code'] ?? ''));
        $isCash = $method === 'cash';
        $isOnline = $method === 'paymongo';
        // Cash and online payments need no screenshot.
        $noProof = $isCash || $isOnline;

        $quote = $this->quote($items, $modes, $promoCode);

        if ($promoCode !== '' && ! $quote['promo_valid']) {
            throw new DomainException('Promo code is invalid, expired, or does not meet the minimum spend.');
        }

        foreach ($quote['lines'] as $line) {
            $item = $line['item'];

            if ($item->bookingDate && $item->bookingDate < today()->toDateString()) {
                throw new DomainException('One of your selected dates has already passed. Please update that item in your cart.');
            }

            if ($error = $this->cart->validate($item->serviceID, $item->staffID, $item->bookingDate, $item->bookingTime)) {
                throw new DomainException($error);
            }
        }

        if ($quote['has_home'] && $address === '') {
            throw new DomainException('Please enter your home address for the selected home service item(s).');
        }

        if (! $noProof && ! $proof) {
            throw new DomainException('Please attach the screenshot of your payment.');
        }

        // One proof image is shared by every booking made in this checkout (same as the old site).
        $stored = (! $noProof && $proof) ? $this->storeProof($customer, $proof) : null;

        try {
            $bookings = DB::transaction(function () use ($customer, $quote, $type, $method, $isCash, $isOnline, $noProof, $address, $extraRequest, $stored) {
                $created = new Collection;

                foreach ($quote['lines'] as $line) {
                    $item = $line['item'];
                    $isHome = $line['mode'] === 'home_service';

                    $booking = Booking::create([
                        'bookingRef' => $this->newReference(),
                        'customerID' => $customer->getKey(),
                        'serviceID' => $item->serviceID,
                        'service' => $item->service->serviceName,
                        'staffID' => $item->staffID ?: null,
                        'bookingDate' => $item->bookingDate,
                        'bookingTime' => $item->bookingTime,
                        'bookingStatus' => 'pending',
                        'paymentStatus' => $noProof ? 'pending' : 'proof-submitted',
                        'payment_type' => $type,
                        'total_amount' => $line['total'],
                        'downpayment_amount' => $line['downpayment'],
                        'discount_amount' => $line['discount'],
                        'discount_reason' => $line['discount'] > 0 ? $quote['discount_reason'] : null,
                        'service_type' => $line['mode'],
                        'home_address' => $isHome ? $address : null,
                        'special_request' => trim(($item->special_request ?? '').($extraRequest !== '' ? "\n".$extraRequest : '')),
                        'payment_proof' => $stored['path'] ?? null,
                        'payment_proof_name' => $stored['name'] ?? null,
                    ]);

                    // An online payment gets its own record when the customer is sent to PayMongo.
                    if (! $isOnline) {
                        Payment::create([
                            'bookingID' => $booking->getKey(),
                            'payType' => $method,
                            'amount' => $type === 'partial' ? $line['downpayment'] : $line['total'],
                            'payStatus' => $isCash ? 'pending' : 'proof-submitted',
                            'referenceNo' => ($isCash ? 'CASH' : 'REF').now()->format('YmdHis').$booking->getKey(),
                            'payment_proof' => $stored['path'] ?? null,
                        ]);
                    }

                    $this->cart->remove($item->cartID, $customer->getKey());
                    $created->push($booking);
                }

                if ($quote['discount'] > 0 && $quote['promo'] && ! $this->promos->markUsed($quote['promo'])) {
                    throw new DomainException('That promo code has just reached its usage limit. Please remove it and try again.');
                }

                UserNotification::send('customer', $customer->getKey(), 'Booking Submitted', 'Your selected booking(s) have been submitted for approval.');

                // Every active owner and manager is told, so a booking is never missed.
                Staff::whereIn('role', ['Owner', 'Manager'])->where('is_active', 1)->get()->each(
                    fn (Staff $s) => UserNotification::send('staff', $s->getKey(), 'New Booking', 'New booking(s) from '.$customer->full_name.' need approval.')
                );

                return $created;
            });
        } catch (Throwable $e) {
            // The booking was not saved, so do not leave the uploaded image behind.
            if ($stored) {
                Storage::disk('public')->delete($stored['path']);
            }

            throw $e;
        }

        // Logging happens after the save, so a logging problem can never undo a booking.
        foreach ($bookings as $booking) {
            SystemLog::record('customer', $customer->getKey(), 'create_booking', "Created booking #{$booking->bookingRef}");
        }

        return $bookings;
    }

    /* ------------------------------------------------------------------
     | After booking
     ------------------------------------------------------------------ */

    // Customer sends (or re-sends) a payment screenshot for an existing booking.
    public function submitProof(Booking $booking, Customer $customer, UploadedFile $proof): void
    {
        if ((int) $booking->customerID !== (int) $customer->getKey()) {
            throw new DomainException('That booking does not belong to your account.');
        }

        if (in_array($booking->bookingStatus, ['cancelled', 'declined', 'completed'], true)) {
            throw new DomainException('This booking is closed, so payment proof can no longer be uploaded.');
        }

        $stored = $this->storeProof($customer, $proof);

        try {
            DB::transaction(function () use ($booking, $stored) {
                $booking->update([
                    'payment_proof' => $stored['path'],
                    'payment_proof_name' => $stored['name'],
                    'paymentStatus' => 'proof-submitted',
                ]);

                Payment::create([
                    'bookingID' => $booking->getKey(),
                    'payType' => 'gcash',
                    'amount' => $booking->payment_type === 'partial' ? $booking->downpayment_amount : $booking->total_amount,
                    'payStatus' => 'proof-submitted',
                    'referenceNo' => 'REF'.now()->format('YmdHis').$booking->getKey(),
                    'payment_proof' => $stored['path'],
                ]);

                UserNotification::send('customer', $booking->customerID, 'Payment Proof Uploaded', 'Your payment proof has been uploaded. Our team will verify it shortly.');
            });
        } catch (Throwable $e) {
            Storage::disk('public')->delete($stored['path']);

            throw $e;
        }

        SystemLog::record('customer', $customer->getKey(), 'upload_payment_proof', "Uploaded payment proof for #{$booking->bookingRef}");
    }

    /**
     * A customer cancels their own pending or approved booking, up to the cancellation window.
     *
     * @throws DomainException with a message that is safe to show the customer
     */
    public function cancelByCustomer(Booking $booking, int $customerId): void
    {
        $this->assertChangeable($booking, $customerId, 'cancel');

        $paid = in_array($booking->paymentStatus, ['confirmed', 'paid', 'proof-submitted'], true);

        // The status check is part of the UPDATE, so a booking the salon just changed is not overwritten.
        $changed = DB::transaction(function () use ($booking, $customerId, $paid): int {
            $changed = Booking::where('bookingID', $booking->getKey())
                ->where('customerID', $customerId)
                ->whereIn('bookingStatus', ['pending', 'approved'])
                ->update(['bookingStatus' => 'cancelled', 'cancellation_reason' => 'Cancelled by customer']);

            if ($changed > 0) {
                UserNotification::send('customer', $customerId, 'Booking Cancelled', "Your booking {$booking->bookingRef} has been cancelled."
                    .($paid ? ' The salon will contact you about the payment you sent.' : ''));
                $this->notifySalon($booking, 'Booking Cancelled', "{$booking->customer?->full_name} cancelled {$booking->bookingRef} ({$booking->service_name}, {$booking->appointment_at->format('M d, g:i A')})."
                    .($paid ? ' A payment was already sent; arrange the refund or credit.' : ''));
            }

            return $changed;
        });

        if ($changed === 0) {
            throw new DomainException('This booking was just updated by the salon. Please refresh and try again.');
        }

        SystemLog::record('customer', $customerId, 'cancel_booking', "Cancelled booking #{$booking->bookingRef}");
    }

    /**
     * A customer moves their booking to another date, time or staff member.
     *
     * Allowed up to the cancellation window and at most "max_reschedule_count" times. The booking goes
     * back to "pending" so the salon approves the new slot; the payment already sent stays with it.
     *
     * @throws DomainException with a message that is safe to show the customer
     */
    public function rescheduleByCustomer(Booking $booking, int $customerId, string $date, string $time, int $staffId): void
    {
        $this->assertChangeable($booking, $customerId, 'reschedule');

        if ($booking->reschedules_left <= 0) {
            throw new DomainException('This booking has already been rescheduled the maximum number of times.');
        }

        $newStart = Carbon::parse($date.' '.$time);

        if ($newStart->isSameDay($booking->bookingDate) && $newStart->format('H:i:s') === Carbon::parse($booking->bookingTime)->format('H:i:s') && $staffId === (int) $booking->staffID) {
            throw new DomainException('Choose a different date, time or staff member.');
        }

        if ($newStart->copy()->subHours(SystemPolicy::cancellationWindowHours())->lte(now())) {
            throw new DomainException('The new time must be at least '.SystemPolicy::cancellationWindowHours().' hours from now.');
        }

        if ($error = $this->cart->validate((int) $booking->serviceID, $staffId, $date, $time, (int) $booking->getKey())) {
            throw new DomainException($error);
        }

        $old = $booking->appointment_at->format('M d, g:i A');

        $changed = DB::transaction(function () use ($booking, $customerId, $newStart, $staffId, $old): int {
            $changed = Booking::where('bookingID', $booking->getKey())
                ->where('customerID', $customerId)
                ->whereIn('bookingStatus', ['pending', 'approved'])
                ->where('reschedule_count', $booking->reschedule_count)
                ->update([
                    'bookingDate' => $newStart->toDateString(),
                    'bookingTime' => $newStart->format('H:i:s'),
                    'staffID' => $staffId,
                    'bookingStatus' => 'pending',
                    'attendance_status' => 'pending',
                    'reschedule_count' => (int) $booking->reschedule_count + 1,
                ]);

            if ($changed > 0) {
                $new = $newStart->format('M d, g:i A');
                UserNotification::send('customer', $customerId, 'Booking Rescheduled', "Your booking {$booking->bookingRef} was moved to {$new}. The salon will confirm the new time.");
                $this->notifySalon($booking, 'Booking Rescheduled', "{$booking->customer?->full_name} moved {$booking->bookingRef} ({$booking->service_name}) from {$old} to {$new}. It needs approval again.");
            }

            return $changed;
        });

        if ($changed === 0) {
            throw new DomainException('This booking was just updated by the salon. Please refresh and try again.');
        }

        SystemLog::record('customer', $customerId, 'reschedule_booking', "Rescheduled booking #{$booking->bookingRef} from {$old} to {$newStart->format('M d, g:i A')}");
    }

    /**
     * Shared checks for customer cancel / reschedule, with a message that explains the rule.
     */
    private function assertChangeable(Booking $booking, int $customerId, string $action): void
    {
        if ((int) $booking->customerID !== $customerId) {
            throw new DomainException('That booking does not belong to your account.');
        }

        if (! in_array($booking->bookingStatus, ['pending', 'approved'], true) || $booking->is_walkin) {
            throw new DomainException("This booking can no longer be {$action}".($action === 'cancel' ? 'led.' : 'd.'));
        }

        if (now()->gte($booking->change_deadline)) {
            $hours = SystemPolicy::cancellationWindowHours();

            throw new DomainException("Bookings can only be {$action}".($action === 'cancel' ? 'led' : 'd')." up to {$hours} hours before the appointment. Please call the salon instead.");
        }
    }

    /**
     * Tell the owners, managers and the assigned staff member about a customer's change.
     */
    private function notifySalon(Booking $booking, string $title, string $message): void
    {
        Staff::where('is_active', 1)
            ->where(fn ($query) => $query->whereIn('role', ['Owner', 'Manager'])->orWhere('staffID', $booking->staffID))
            ->get()
            ->each(fn (Staff $staff) => UserNotification::send('staff', (int) $staff->getKey(), $title, $message));
    }

    /* ------------------------------------------------------------------
     | Helpers
     ------------------------------------------------------------------ */

    // Like JA-2026-1007-482, and guaranteed not to clash with an existing booking.
    private function newReference(): string
    {
        for ($i = 0; $i < 30; $i++) {
            $ref = 'JA-'.now()->format('Y').'-'.now()->format('md').'-'.random_int(100, 999);

            if (! Booking::where('bookingRef', $ref)->exists()) {
                return $ref;
            }
        }

        return 'JA-'.now()->format('Y-md').'-'.strtoupper(Str::random(6));
    }

    // Save the image on the "public" disk (storage/app/public/payments) and return its name and path.
    private function storeProof(Customer $customer, UploadedFile $file): array
    {
        $extension = strtolower(preg_replace('/[^a-z0-9]/i', '', $file->getClientOriginalExtension())) ?: 'jpg';
        $safeName = preg_replace('/[^A-Za-z0-9]/', '', $customer->full_name);
        $name = $safeName.'_'.now()->format('YmdHis').'_'.Str::lower(Str::random(4)).'.'.$extension;

        $path = $file->storeAs('payments', $name, 'public');

        if (! $path) {
            throw new DomainException('Payment proof upload failed. Please try again.');
        }

        return ['name' => $name, 'path' => $path];
    }
}
