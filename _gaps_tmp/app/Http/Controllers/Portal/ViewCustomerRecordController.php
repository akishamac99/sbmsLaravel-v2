<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Customer;
use App\Support\PaymentLedger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * View Customer Records: a customer's booking history.
 * Owner and Head Manager see every booking; Salon Staff see only the customers they have served and only their own bookings with them.
 */
class ViewCustomerRecordController extends Controller
{
    /** Staff only: the customers they have been booked with. */
    public function index(): View
    {
        $staffId = (int) Auth::guard('staff')->id();

        $customers = Customer::whereHas('bookings', fn ($q) => $q->where('staffID', $staffId))
            ->withCount(['bookings as visits_count' => fn ($q) => $q->where('staffID', $staffId)])
            ->orderBy('firstName')
            ->paginate(25);

        return view('portal.my-customers', ['customers' => $customers, 'role' => 'staff']);
    }

    public function show(Customer $customer): View|RedirectResponse
    {
        $user = Auth::guard('staff')->user();
        $role = $user->role_slug;
        $ownOnly = $role === 'staff';

        $bookings = Booking::with(['serviceItem', 'staff'])
            ->where('customerID', $customer->getKey())
            ->when($ownOnly, fn ($q) => $q->where('staffID', $user->getKey()))
            ->orderByDesc('bookingDate')
            ->orderByDesc('bookingTime')
            ->paginate(20);

        if ($ownOnly && $bookings->total() === 0) {
            return redirect()->route('staff.customers')->with('error', 'You can only open customers you have served.');
        }

        $counts = Booking::where('customerID', $customer->getKey())
            ->when($ownOnly, fn ($q) => $q->where('staffID', $user->getKey()))
            ->selectRaw('"bookingStatus" as status, COUNT(*) as total')
            ->groupBy('bookingStatus')
            ->pluck('total', 'status');

        return view('portal.customer-record', [
            'customer' => $customer,
            'bookings' => $bookings,
            'counts' => $counts,
            'role' => $role,
            'ownOnly' => $ownOnly,
        ]);
    }
}
