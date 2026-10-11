<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\ServiceCategory;
use App\Models\Staff;
use App\Services\BookingService;
use App\Services\CartService;
use App\Support\TimeSlots;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;

// Replaces customer/services.php: the service catalog, plus the booking cart that sits beside it.
class ServiceController extends Controller
{
    public function __construct(private CartService $cart, private BookingService $bookings) {}

    public function index()
    {
        $customer = $this->customer();

        $categories = ServiceCategory::with(['services' => fn ($q) => $q->catalog()->orderBy('servicePrice')])
            ->orderBy('catID')
            ->get()
            ->filter(fn ($category) => $category->services->isNotEmpty())
            ->values();

        $cartItems = $this->cart->items($customer->getKey());
        $selection = session('checkout_selection', ['cart_ids' => [], 'service_modes' => []]);

        // Items ticked for checkout. If nothing was chosen before, everything starts ticked.
        $selectedIds = ! empty($selection['cart_ids'])
            ? array_map('intval', $selection['cart_ids'])
            : $cartItems->pluck('cartID')->map(fn ($id) => (int) $id)->all();

        $cartData = $cartItems->map(fn ($item) => [
            'cartID' => $item->cartID,
            'serviceID' => $item->serviceID,
            'serviceName' => $item->service->serviceName,
            'servicePrice' => (float) $item->service->servicePrice,
            'staffID' => $item->staffID,
            'bookingDate' => $item->bookingDate,
            'bookingTime' => $item->bookingTime,
            'notes' => $item->notes ?? '',
            'special_request' => $item->special_request ?? '',
        ])->values();

        $homeFee = $this->bookings->homeServiceFee();

        return view('customer.services', [
            'categories' => $categories,
            'totalServices' => $categories->sum(fn ($c) => $c->services->count()),
            'eventCategoryCount' => $categories->where('is_event_type', 1)->count(),
            'staffCount' => Staff::where('is_active', 1)->where('is_archived', 0)->whereIn('role', ['Staff', 'Manager'])->count(),
            'cartItems' => $cartItems,
            'cartData' => $cartData,
            'selectedIds' => $selectedIds,
            'modes' => $selection['service_modes'] ?? [],
            'homeEligible' => $cartItems->mapWithKeys(fn ($item) => [
                $item->cartID => $item->service->is_home_service && BookingService::isMakeup($item->service),
            ]),
            'homeFee' => $homeFee,
            'downpaymentPercent' => $this->bookings->downpaymentPercent(),
            'hasUnpaid' => $this->bookings->hasUnpaid($customer->getKey()),
            'timeSlots' => TimeSlots::groups(),
            'minDate' => today()->toDateString(),
            'maxDate' => today()->addDays(30)->toDateString(),
        ]);
    }

    public function addToCart(Request $request)
    {
        $data = $request->validate(
            ['service_id' => ['required', 'integer', 'exists:service,serviceID']] + $this->scheduleRules(),
            $this->scheduleMessages()
        );

        $error = $this->cart->add(
            $this->customer()->getKey(),
            $request->session()->getId(),
            (int) $data['service_id'],
            $data['staff_id'] ?? null,
            $data['booking_date'],
            $data['booking_time'],
            $data['notes'] ?? null,
            $data['special_request'] ?? null
        );

        if ($error) {
            return redirect()->route('customer.services')->with('error', $error)->withInput();
        }

        return redirect()->route('customer.services')->with('status', 'Service added to your cart.');
    }

    public function updateCart(Request $request, int $cart)
    {
        $data = $request->validate($this->scheduleRules(), $this->scheduleMessages());

        $error = $this->cart->update(
            $cart,
            $this->customer()->getKey(),
            $data['staff_id'] ?? null,
            $data['booking_date'],
            $data['booking_time'],
            $data['notes'] ?? null,
            $data['special_request'] ?? null
        );

        if ($error) {
            return redirect()->route('customer.services')->with('error', $error);
        }

        return redirect()->route('customer.services')->with('status', 'Cart item updated.');
    }

    public function removeCart(int $cart)
    {
        $this->cart->remove($cart, $this->customer()->getKey());

        return redirect()->route('customer.services')->with('status', 'Item removed from your cart.');
    }

    // "Checkout Selected": remember which items were ticked (and in-salon/home choices), then go to checkout.
    public function selectForCheckout(Request $request)
    {
        $customer = $this->customer();
        $items = $this->cart->items($customer->getKey());

        // Only items that really are in this customer's cart can be selected.
        $selectedIds = collect($request->input('selected_cart', []))
            ->map(fn ($id) => (int) $id)
            ->intersect($items->pluck('cartID')->map(fn ($id) => (int) $id))
            ->values()
            ->all();

        if (empty($selectedIds)) {
            return back()->with('error', 'Select at least one service in the cart before checkout.');
        }

        if ($this->bookings->hasUnpaid($customer->getKey())) {
            return back()->with('error', 'Please settle your unpaid booking before proceeding to checkout.');
        }

        $modes = [];

        foreach ($items->whereIn('cartID', $selectedIds) as $item) {
            $requested = $request->input("service_mode.{$item->cartID}", 'salon');

            $modes[$item->cartID] = ($requested === 'home_service' && $item->service->is_home_service && BookingService::isMakeup($item->service))
                ? 'home_service'
                : 'salon';
        }

        session(['checkout_selection' => ['cart_ids' => $selectedIds, 'service_modes' => $modes]]);

        // The checkout page is built in the next step. Until then, stay here.
        if (! Route::has('customer.checkout')) {
            return back()->with('status', 'Selection saved. The checkout page is coming in the next step.');
        }

        return redirect()->route('customer.checkout');
    }

    /* ------------------------------------------------------------------ */

    private function customer(): Customer
    {
        return Auth::guard('customer')->user();
    }

    // Date: today up to 30 days ahead. Time: one of the real slots.
    private function scheduleRules(): array
    {
        return [
            'staff_id' => ['nullable', 'integer'],
            'booking_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today', 'before_or_equal:'.today()->addDays(30)->toDateString()],
            'booking_time' => ['required', Rule::in(TimeSlots::values())],
            'notes' => ['nullable', 'string', 'max:500'],
            'special_request' => ['nullable', 'string', 'max:500'],
        ];
    }

    private function scheduleMessages(): array
    {
        return [
            'booking_date.required' => 'Please select a booking date and time.',
            'booking_time.required' => 'Please select a booking date and time.',
            'booking_date.after_or_equal' => 'You cannot book a date that has already passed.',
            'booking_date.before_or_equal' => 'Bookings can be made up to 30 days ahead.',
            'booking_time.in' => 'Please choose one of the listed time slots.',
        ];
    }
}
