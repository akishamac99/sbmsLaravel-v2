<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Service;
use App\Models\Staff;
use App\Models\UserNotification;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function customer()
    {
        $customer = Auth::guard('customer')->user();
        $mine = fn () => Booking::where('customerID', $customer->getKey());

        return view('dashboards.customer', [
            'user' => $customer,
            'upcoming' => $mine()->whereIn('bookingStatus', ['pending', 'approved'])->whereDate('bookingDate', '>=', today())->count(),
            'completed' => $mine()->where('bookingStatus', 'completed')->count(),
            'unread' => UserNotification::forUser('customer', $customer->getKey())->where('is_read', 0)->count(),
            'recent' => $mine()->with('serviceItem')->latest('created_at')->limit(5)->get(),
        ]);
    }

    public function owner()
    {
        return $this->portal('owner');
    }

    public function manager()
    {
        return $this->portal('manager');
    }

    public function staff()
    {
        return $this->portal('staff');
    }

    // Owner, manager and staff dashboards share one view. Staff only see their own bookings.
    private function portal(string $role)
    {
        $user = Auth::guard('staff')->user();
        $scope = fn () => $role === 'staff' ? Booking::where('staffID', $user->getKey()) : Booking::query();

        $metrics = [
            ['fa-calendar-check', 'Total Bookings', $scope()->count(), null],
            ['fa-hourglass-half', 'Pending Approval', $scope()->where('bookingStatus', 'pending')->count(), 'var(--warning)'],
            ['fa-thumbs-up', 'Approved', $scope()->where('bookingStatus', 'approved')->count(), null],
            ['fa-check-circle', 'Completed', $scope()->where('bookingStatus', 'completed')->count(), 'var(--success)'],
        ];

        if ($role !== 'staff') {
            $revenue = (float) Booking::where('bookingStatus', 'completed')->sum('total_amount');

            $metrics[] = ['fa-peso-sign', 'Completed Revenue', 'PHP ' . number_format($revenue), null];
            $metrics[] = ['fa-receipt', 'Pending Payments', Payment::where('payStatus', 'proof-submitted')->count(), 'var(--rose-gold)'];
        }

        if ($role === 'owner') {
            $metrics[] = ['fa-users', 'Customers', Customer::count(), null];
            $metrics[] = ['fa-id-card', 'Active Staff', Staff::where('is_active', 1)->count(), null];
            $metrics[] = ['fa-scissors', 'Active Services', Service::active()->count(), null];
        }

        return view('dashboards.portal', [
            'user' => $user,
            'role' => $role,
            'metrics' => $metrics,
            'recent' => $scope()->with(['customer', 'serviceItem'])->latest('created_at')->limit(10)->get(),
        ]);
    }
}
