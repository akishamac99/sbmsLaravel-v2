<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\SystemLog;
use App\Models\UserNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function index(): View
    {
        $staffId = (int) Auth::guard('staff')->id();
        $bookings = Booking::with(['customer', 'serviceItem'])
            ->where('staffID', $staffId)
            ->latest('bookingDate')
            ->latest('bookingTime')
            ->paginate(20);

        return view('staff.bookings', ['bookings' => $bookings]);
    }

    public function approve(Booking $booking): RedirectResponse
    {
        if ((int) $booking->staffID !== (int) Auth::guard('staff')->id() || $booking->bookingStatus !== 'pending') {
            return redirect()->route('staff.bookings')->with('error', 'You can only approve pending bookings assigned to you.');
        }

        DB::transaction(function () use ($booking): void {
            $booking->update(['bookingStatus' => 'approved']);
            UserNotification::send('customer', (int) $booking->customerID, 'Booking Approved', "Your booking {$booking->bookingRef} has been approved by your selected staff.");
        });

        SystemLog::record('staff', (int) Auth::guard('staff')->id(), 'approve_booking', "Approved booking {$booking->bookingRef}");

        return redirect()->route('staff.bookings')->with('status', 'Booking approved.');
    }

    public function attendance(): View
    {
        $bookings = Booking::with(['customer', 'serviceItem'])
            ->where('staffID', Auth::guard('staff')->id())
            ->where('bookingStatus', 'approved')
            ->where('booking_source', Booking::SOURCE_ONLINE)
            ->orderBy('bookingDate')
            ->orderBy('bookingTime')
            ->paginate(20);

        return view('staff.attendance', ['bookings' => $bookings]);
    }

    public function markAttendance(Request $request, Booking $booking): RedirectResponse
    {
        $validated = $request->validate([
            'attendance_status' => ['required', 'in:present,no-show'],
        ]);

        if ((int) $booking->staffID !== (int) Auth::guard('staff')->id() || $booking->bookingStatus !== 'approved' || $booking->is_walkin) {
            return redirect()->route('staff.attendance')->with('error', 'Attendance can only be recorded for your approved bookings.');
        }

        DB::transaction(function () use ($booking, $validated): void {
            $booking->update([
                'attendance_status' => $validated['attendance_status'],
                'bookingStatus' => $validated['attendance_status'] === 'present' ? 'completed' : 'no-show',
            ]);

            if ($validated['attendance_status'] === 'present') {
                UserNotification::send('customer', (int) $booking->customerID, 'Service Completed', 'Your service has been completed. Thank you for visiting!');
            }
        });

        SystemLog::record('staff', (int) Auth::guard('staff')->id(), 'mark_attendance', "Marked {$booking->bookingRef} as {$validated['attendance_status']}");

        return redirect()->route('staff.attendance')->with('status', 'Attendance recorded.');
    }
}
