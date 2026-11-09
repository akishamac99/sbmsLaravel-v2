<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Permission;
use App\Models\SystemLog;
use App\Models\UserNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

// One booking, in full, for staff / manager / owner.
//  - staff:   can open only the bookings assigned to them (read only)
//  - manager: needs the "bookings" permission (and "edit" to cancel)
//  - owner:   can open and cancel any booking
class BookingDetailController extends Controller
{
    public function show(Booking $booking): View|RedirectResponse
    {
        $user = Auth::guard('staff')->user();
        $role = $user->role_slug;

        if ($role === 'staff' && (int) $booking->staffID !== (int) $user->getKey()) {
            return redirect()->route('staff.bookings')->with('error', 'You can only open bookings assigned to you.');
        }

        $booking->load(['customer', 'serviceItem', 'staff', 'payments', 'feedback']);

        return view('portal.booking-details', [
            'booking' => $booking,
            'role' => $role,
            'canCancel' => $this->canCancel($role, $booking),
        ]);
    }

    public function cancel(Request $request, Booking $booking): RedirectResponse
    {
        $user = Auth::guard('staff')->user();
        $role = $user->role_slug;

        if (! $this->canCancel($role, $booking)) {
            return back()->with('error', 'This booking cannot be cancelled.');
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ], [
            'reason.required' => 'Please enter a reason for the cancellation.',
        ]);

        // The status check is inside the UPDATE, so two people cancelling at once cannot both succeed.
        $changed = DB::transaction(function () use ($booking, $validated): int {
            $changed = Booking::where('bookingID', $booking->getKey())
                ->whereIn('bookingStatus', ['pending', 'approved'])
                ->update([
                    'bookingStatus' => 'cancelled',
                    'cancellation_reason' => 'Cancelled by the salon: ' . $validated['reason'],
                ]);

            if ($changed > 0) {
                UserNotification::send(
                    'customer',
                    (int) $booking->customerID,
                    'Booking Cancelled',
                    "Your booking {$booking->bookingRef} was cancelled by the salon. Reason: {$validated['reason']}"
                );
            }

            return $changed;
        });

        if ($changed === 0) {
            return back()->with('error', 'This booking was already updated by someone else.');
        }

        SystemLog::record($role, (int) $user->getKey(), 'cancel_booking', "Cancelled booking {$booking->bookingRef}: {$validated['reason']}");

        $paid = in_array($booking->paymentStatus, ['confirmed', 'paid'], true);

        return back()->with('status', $paid
            ? 'Booking cancelled. Its payment was already confirmed, so any refund must be handled outside the system.'
            : 'Booking cancelled.');
    }

    // Only the owner, or a manager allowed to edit bookings, and only while the booking is still open.
    private function canCancel(string $role, Booking $booking): bool
    {
        if (! in_array($booking->bookingStatus, ['pending', 'approved'], true)) {
            return false;
        }

        return $role === 'owner'
            || ($role === 'manager' && Permission::allows('Manager', 'bookings', 'edit'));
    }
}