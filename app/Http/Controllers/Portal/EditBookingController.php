<?php

namespace App\Http\Controllers\Portal;

use App\Actions\Bookings\UpdateBookingDetails;
use App\Exceptions\BusinessRuleViolation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\UpdateBookingRequest;
use App\Models\Booking;
use App\Models\Staff;
use App\Services\StaffMatcher;
use App\Support\BookingAccess;
use App\Support\TimeSlots;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/** Update Booking and Assign / Reassign Staff, for the Owner, Head Manager and Salon Staff. */
class EditBookingController extends Controller
{
    public function edit(Booking $booking, StaffMatcher $matcher): View|RedirectResponse
    {
        $user = Auth::guard('staff')->user();

        if (! BookingAccess::canEdit($user, $booking)) {
            return redirect()->route($user->role_slug.'.bookings.show', $booking)->with('error', 'You cannot change this booking.');
        }

        $booking->load(['customer', 'serviceItem.category', 'staff']);
        $canReassign = BookingAccess::canReassign($user);

        $staffOptions = $canReassign
            ? Staff::where('is_active', 1)->where('is_archived', 0)->whereIn('role', ['Owner', 'Manager', 'Staff'])->orderBy('firstName')->get()
                ->filter(fn (Staff $s) => $booking->serviceItem && $matcher->isQualified($s, $booking->serviceItem))->values()
            : collect();

        return view('portal.edit-booking', [
            'booking' => $booking,
            'role' => $user->role_slug,
            'canReassign' => $canReassign,
            'staffOptions' => $staffOptions,
            'slotGroups' => TimeSlots::groups(),
        ]);
    }

    public function update(UpdateBookingRequest $request, Booking $booking, UpdateBookingDetails $update): RedirectResponse
    {
        $user = Auth::guard('staff')->user();

        try {
            $update->handle($user, $booking, $request->validated());
        } catch (BusinessRuleViolation $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route($user->role_slug.'.bookings.show', $booking)->with('status', 'Booking updated.');
    }
}
