<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\BookingService;
use App\Services\CartService;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;

// Replaces customer/checkout.php: review the selected cart items, apply a promo, pay, and place the booking(s).
class CheckoutController extends Controller
{
    public function __construct(private CartService $cart, private BookingService $bookings) {}

    public function show()
    {
        $customer = $this->customer();
        [$items, $modes] = $this->selected($customer);

        if ($items->isEmpty()) {
            return redirect()->route('customer.services');
        }

        if ($this->bookings->hasUnpaid($customer->getKey())) {
            return redirect()->route($this->afterBookingRoute())
                ->with('error', 'You have a pending or unpaid booking. Please settle it before placing a new one.');
        }

        $promoCode = (string) session('checkout_promo', '');
        $quote = $this->bookings->quote($items, $modes, $promoCode);

        // A saved promo that no longer qualifies (for example the selection changed) is dropped quietly.
        if ($promoCode !== '' && ! $quote['promo_valid']) {
            session()->forget('checkout_promo');
            $promoCode = '';
            $quote = $this->bookings->quote($items, $modes, null);
            session()->now('error', 'Your promo code no longer applies to this selection, so it was removed.');
        }

        return view('customer.checkout', [
            'customer' => $customer,
            'quote' => $quote,
            'promoCode' => $promoCode,
        ]);
    }

    // The Apply / Remove promo buttons. They post the whole form, so nothing the customer typed is lost.
    public function promo(Request $request)
    {
        $customer = $this->customer();
        [$items, $modes] = $this->selected($customer);

        if ($items->isEmpty()) {
            return redirect()->route('customer.services');
        }

        $keep = $request->only(['payment_type', 'payment_method', 'home_address', 'special_request']);

        if ($request->boolean('remove_promo')) {
            session()->forget('checkout_promo');

            return redirect()->route('customer.checkout')->withInput($keep)->with('status', 'Promo code removed.');
        }

        $code = trim((string) $request->input('promo_code'));

        if ($code === '') {
            return redirect()->route('customer.checkout')->withInput($keep)->with('error', 'Please enter a promo code first.');
        }

        $quote = $this->bookings->quote($items, $modes, $code);

        if (! $quote['promo_valid']) {
            return redirect()->route('customer.checkout')->withInput($keep)
                ->with('error', 'Promo code is invalid, expired, or does not meet the minimum spend.');
        }

        session(['checkout_promo' => $code]);

        return redirect()->route('customer.checkout')->withInput($keep)->with('status', 'Promo code applied.');
    }

    public function place(Request $request)
    {
        $customer = $this->customer();
        [$items, $modes] = $this->selected($customer);

        if ($items->isEmpty()) {
            return redirect()->route('customer.services');
        }

        $methods = filled(config('salon.bank_account_number')) ? ['gcash', 'bank_transfer'] : ['gcash'];

        if (filled(config('services.paymongo.secret'))) {
            $methods[] = 'paymongo';
        }

        $data = $request->validate([
            'payment_type' => ['required', Rule::in(['full', 'partial'])],
            'payment_method' => ['required', Rule::in($methods)],
            'home_address' => ['nullable', 'string', 'max:500'],
            'special_request' => ['nullable', 'string', 'max:500'],
            // Pictures only (no SVG, which can carry scripts). The browser shrinks big photos first.
            'payment_proof' => [Rule::requiredIf($request->input('payment_method') !== 'paymongo'), 'nullable', 'file', 'mimes:jpg,jpeg,png,gif,webp', 'max:10240'],
        ], self::proofMessages());

        if ($this->bookings->hasUnpaid($customer->getKey())) {
            return redirect()->route($this->afterBookingRoute())
                ->with('error', 'You have a pending or unpaid booking. Please settle it before placing a new one.');
        }

        try {
            $created = $this->bookings->checkout(
                $customer,
                $items,
                $modes,
                $data + ['promo_code' => (string) session('checkout_promo', '')],
                $request->file('payment_proof')
            );
        } catch (DomainException $e) {
            return redirect()->route('customer.checkout')
                ->withInput($request->only(['payment_type', 'payment_method', 'home_address', 'special_request']))
                ->with('error', $e->getMessage());
        }

        session()->forget(['checkout_selection', 'checkout_promo']);

        // Pay online: go straight to PayMongo's page for the first booking.
        if ($data['payment_method'] === 'paymongo') {
            return app(OnlinePaymentController::class)->start($created->first()->bookingRef);
        }

        $message = $created->count() === 1
            ? 'Your booking was submitted for approval.'
            : $created->count().' bookings were submitted for approval.';

        return redirect()->route($this->afterBookingRoute())->with('status', $message);
    }

    /* ------------------------------------------------------------------ */

    /**
     * Error messages for the payment screenshot, shared with the "upload payment" page.
     *
     * @return array<string, string>
     */
    public static function proofMessages(): array
    {
        $serverLimit = (int) floor(UploadedFile::getMaxFilesize() / 1024 / 1024);

        return [
            'payment_method.in' => 'Choose how you sent the payment.',
            'payment_proof.required' => 'Please attach the screenshot of your payment.',
            'payment_proof.uploaded' => "The screenshot could not be uploaded. It may be larger than the server allows ({$serverLimit} MB). Please try a smaller screenshot.",
            'payment_proof.mimes' => 'Payment proof must be a JPG, PNG, GIF or WEBP image.',
            'payment_proof.max' => 'Payment proof must be 10 MB or smaller.',
        ];
    }

    private function customer(): Customer
    {
        return Auth::guard('customer')->user();
    }

    // The cart items ticked on the services page (only ones that are really in this customer's cart), and their modes.
    private function selected(Customer $customer): array
    {
        $selection = session('checkout_selection', ['cart_ids' => [], 'service_modes' => []]);
        $ids = array_map('intval', $selection['cart_ids'] ?? []);

        $items = $this->cart->items($customer->getKey())
            ->filter(fn ($item) => in_array((int) $item->cartID, $ids, true))
            ->values();

        return [$items, $selection['service_modes'] ?? []];
    }

    // "My Bookings" once that page exists (next step); the services page until then.
    private function afterBookingRoute(): string
    {
        return Route::has('customer.bookings') ? 'customer.bookings' : 'customer.services';
    }
}
