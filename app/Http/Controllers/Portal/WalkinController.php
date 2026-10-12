<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Permission;
use App\Models\Service;
use App\Models\Staff;
use App\Models\SystemLog;
use App\Models\SystemPolicy;
use App\Models\UserNotification;
use App\Services\StaffMatcher;
use App\Support\TimeSlots;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Walk-in customers: someone standing at the front desk right now.
 *
 * The flow is always the same, in this order:
 *   1. customer (name, contact number, optional email)
 *   2. service
 *   3. time slot today
 *   4. staff member free for that service and slot (or "first available")
 *   5. payment collected at the desk
 *
 * A walk-in is checked in on creation: it is approved and marked present straight away, so it
 * never waits for the assigned staff member to confirm it and never shows Present / No-show.
 * The only follow-up step is "Complete" once the service is done.
 */
class WalkinController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        if (! $this->walkinsEnabled()) {
            return redirect()->route($this->role().'.dashboard')->with('error', 'Walk-in bookings are currently disabled.');
        }

        $filter = $request->query('show') === 'all' ? 'all' : 'today';

        $bookings = Booking::with(['customer', 'serviceItem', 'staff'])
            ->walkins()
            ->when($filter === 'today', fn ($query) => $query->whereDate('bookingDate', today()))
            ->orderByRaw("CASE WHEN \"bookingStatus\" = 'approved' THEN 0 ELSE 1 END")
            ->latest('bookingDate')
            ->latest('bookingTime')
            ->paginate(20)
            ->withQueryString();

        $services = Service::catalog()->with('category')->orderBy('serviceName')->get();

        return view('portal.walkin', [
            'bookings' => $bookings,
            'filter' => $filter,
            'services' => $services,
            'slots' => $this->todaysSlots(),
            'downpaymentPercent' => (float) SystemPolicy::value('downpayment_percent', 20),
            'role' => $this->role(),
            'canCreate' => $this->canManage(),
        ]);
    }

    /**
     * Staff who can take this service at this time today (used to fill the staff list).
     */
    public function availableStaff(Request $request, StaffMatcher $matcher): JsonResponse
    {
        $validated = $request->validate([
            'service_id' => ['required', 'integer', 'exists:service,serviceID'],
            'time' => ['required', Rule::in(TimeSlots::values())],
        ]);

        $staff = $matcher
            ->availableStaff(today()->toDateString(), $validated['time'], (int) $validated['service_id'])
            ->filter(fn (Staff $member): bool => in_array($member->role, ['Staff', 'Manager'], true))
            ->map(fn (Staff $member): array => [
                'id' => (int) $member->getKey(),
                'name' => $member->full_name,
                'specialization' => $member->specialization ?: 'General',
            ])
            ->values();

        return response()->json(['staff' => $staff]);
    }

    public function store(Request $request, StaffMatcher $matcher): RedirectResponse
    {
        if (! $this->walkinsEnabled()) {
            return back()->with('error', 'Walk-in bookings are currently disabled.');
        }

        if (! $this->canManage()) {
            return back()->with('error', 'You do not have permission to create walk-ins.');
        }

        $request->merge(['customer_email' => Str::lower(trim((string) $request->input('customer_email'))) ?: null]);

        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:100'],
            'contact_number' => ['required', 'string', 'max:20'],
            'customer_email' => ['nullable', 'email', 'max:100'],
            'service_id' => ['required', 'integer', 'exists:service,serviceID'],
            'booking_time' => ['required', Rule::in(TimeSlots::values())],
            'staff_id' => ['nullable', 'integer', 'exists:salon_staff,staffID'],
            'payment_method' => ['required', Rule::in(['cash', 'gcash'])],
            'payment_type' => ['required', Rule::in(['full', 'partial'])],
        ], [
            'booking_time.in' => 'Choose one of the time slots listed for today.',
        ]);

        $date = today()->toDateString();
        $service = Service::catalog()->findOrFail($validated['service_id']);

        if (! in_array($validated['booking_time'], $this->todaysSlots(), true)) {
            return back()->withInput()->with('error', 'That time slot has already passed. Choose the current or a later slot.');
        }

        $staff = $this->pickStaff($matcher, $validated['staff_id'] ?? null, $date, $validated['booking_time'], (int) $service->getKey());

        if (! $staff) {
            return back()->withInput()->with('error', empty($validated['staff_id'])
                ? 'No qualified staff member is free for that service at that time. Try another time slot.'
                : 'The selected staff member is no longer free for that service at that time.');
        }

        $customer = $this->findCustomer($validated['contact_number'], $validated['customer_email']);

        if ($customer && $validated['customer_email'] && $customer->email && $customer->email !== $validated['customer_email']) {
            return back()->withInput()->with('error', 'That contact number belongs to a customer with a different email address. Check the details and try again.');
        }

        $amount = (float) $service->servicePrice;
        $downpayment = round($amount * ((float) SystemPolicy::value('downpayment_percent', 20) / 100), 2);
        $paidNow = $validated['payment_type'] === 'partial' ? $downpayment : $amount;

        $booking = DB::transaction(function () use ($validated, $customer, $service, $staff, $date, $amount, $downpayment, $paidNow): Booking {
            $customer = $this->saveCustomer($customer, $validated);

            $booking = Booking::create([
                'bookingRef' => 'WI-'.now()->format('ymd').'-'.Str::upper(Str::random(4)),
                'customerID' => $customer->getKey(),
                'serviceID' => $service->getKey(),
                'service' => $service->serviceName,
                'staffID' => $staff->getKey(),
                'bookingDate' => $date,
                'bookingTime' => $validated['booking_time'],
                'bookingStatus' => 'approved',
                'paymentStatus' => $validated['payment_type'] === 'full' ? 'paid' : 'confirmed',
                'payment_type' => $validated['payment_type'],
                'total_amount' => $amount,
                'downpayment_amount' => $downpayment,
                'attendance_status' => 'present',
                'booking_source' => Booking::SOURCE_WALKIN,
            ]);

            Payment::create([
                'bookingID' => $booking->getKey(),
                'payType' => $validated['payment_method'],
                'amount' => $paidNow,
                'payStatus' => 'paid',
                'referenceNo' => 'WALKIN-'.$booking->bookingRef,
            ]);

            UserNotification::send('staff', (int) $staff->getKey(), 'Walk-in assigned', "{$customer->full_name} is here for {$service->serviceName} at ".Carbon::parse($validated['booking_time'])->format('g:i A').'.');

            return $booking;
        });

        SystemLog::record($this->role(), (int) Auth::guard('staff')->id(), 'create_walkin', "Created walk-in {$booking->bookingRef} for {$staff->full_name}");

        return redirect()->route($this->role().'.walkin')->with('status', "Walk-in checked in with {$staff->full_name}.");
    }

    /**
     * The service is done: close the walk-in and record any balance paid at the desk.
     */
    public function complete(Booking $booking): RedirectResponse
    {
        $user = Auth::guard('staff')->user();

        if (! $booking->is_walkin || $booking->bookingStatus !== 'approved') {
            return back()->with('error', 'Only walk-ins that are in progress can be completed.');
        }

        if ($this->role() === 'staff' && (int) $booking->staffID !== (int) $user->getKey()) {
            return back()->with('error', 'You can only complete walk-ins assigned to you.');
        }

        if ($this->role() === 'manager' && ! $this->canManage()) {
            return back()->with('error', 'You do not have permission to update walk-ins.');
        }

        DB::transaction(function () use ($booking): void {
            $balance = $booking->remaining_balance;

            if ($balance > 0) {
                Payment::create([
                    'bookingID' => $booking->getKey(),
                    'payType' => $booking->payments()->latest('payDate')->value('payType') ?? 'cash',
                    'amount' => $balance,
                    'payStatus' => 'paid',
                    'referenceNo' => 'WALKIN-BAL-'.$booking->bookingRef,
                ]);
            }

            $booking->update([
                'bookingStatus' => 'completed',
                'attendance_status' => 'present',
                'paymentStatus' => 'paid',
            ]);
        });

        SystemLog::record($this->role(), (int) $user->getKey(), 'complete_walkin', "Completed walk-in {$booking->bookingRef}");

        return back()->with('status', 'Walk-in marked as completed.');
    }

    /**
     * The chosen staff member if still free, otherwise (when none was chosen) the first free one.
     */
    private function pickStaff(StaffMatcher $matcher, ?int $staffId, string $date, string $time, int $serviceId): ?Staff
    {
        if ($staffId) {
            $staff = Staff::find($staffId);

            return $staff
                && $staff->is_active
                && ! $staff->is_archived
                && in_array($staff->role, ['Staff', 'Manager'], true)
                && $matcher->isAvailable($staffId, $date, $time, $serviceId)
                ? $staff
                : null;
        }

        return $matcher->availableStaff($date, $time, $serviceId)
            ->first(fn (Staff $staff): bool => in_array($staff->role, ['Staff', 'Manager'], true));
    }

    /**
     * Returning customers are matched by contact number first, then by email.
     */
    private function findCustomer(string $contactNumber, ?string $email): ?Customer
    {
        return Customer::where('contactNumber', $contactNumber)->first()
            ?? ($email ? Customer::where('email', $email)->first() : null);
    }

    /**
     * Create the walk-in customer record, or fill in an email that an earlier walk-in visit lacked.
     *
     * @param  array{customer_name: string, contact_number: string, customer_email: ?string}  $validated
     */
    private function saveCustomer(?Customer $customer, array $validated): Customer
    {
        if ($customer) {
            if ($customer->is_walkin && ! $customer->email && $validated['customer_email']) {
                $customer->update(['email' => $validated['customer_email']]);
            }

            return $customer;
        }

        $nameParts = preg_split('/\s+/', trim($validated['customer_name']), 2);

        return Customer::create([
            'firstName' => $nameParts[0],
            'lastName' => $nameParts[1] ?? '',
            'email' => $validated['customer_email'],
            'password' => Hash::make(Str::random(40)),
            'contactNumber' => $validated['contact_number'],
            'accountStatus' => 'active',
            'is_verified' => 0,
            'is_walkin' => true,
        ]);
    }

    /**
     * Today's bookable slots, starting with the slot that is running right now.
     *
     * @return array<int, string>
     */
    private function todaysSlots(): array
    {
        $currentSlotStart = now()->copy()->setSecond(0)->subMinutes(now()->minute % 30)->format('H:i:s');

        return array_values(array_filter(
            array_keys(TimeSlots::forDate(today()->toDateString())),
            fn (string $slot): bool => $slot >= $currentSlotStart
        ));
    }

    private function walkinsEnabled(): bool
    {
        return (string) SystemPolicy::value('walkins_allowed', '1') === '1';
    }

    private function canManage(): bool
    {
        return $this->role() !== 'manager' || Permission::allows('Manager', 'walkin', 'create');
    }

    private function role(): string
    {
        return Auth::guard('staff')->user()->role_slug;
    }
}
