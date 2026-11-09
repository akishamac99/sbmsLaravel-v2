<?php

namespace App\Actions\Bookings;

use App\Exceptions\BusinessRuleViolation;
use App\Models\Booking;
use App\Models\Staff;
use App\Models\SystemLog;
use App\Models\UserNotification;
use App\Services\StaffMatcher;
use App\Support\BookingAccess;
use App\Support\TimeSlots;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Update Booking and Assign / Reassign Staff.
 * Changes the date, time, notes and (for the Owner or an allowed Manager) the assigned staff member.
 * The staff row is locked and the slot rechecked inside the transaction, the same way checkout does it.
 */
final class UpdateBookingDetails
{
    public function __construct(private readonly StaffMatcher $matcher)
    {
    }

    /** @param array{booking_date: string, booking_time: string, staff_id?: ?int, special_request?: ?string} $data */
    public function handle(Staff $actor, Booking $booking, array $data): void
    {
        if (! BookingAccess::canEdit($actor, $booking)) {
            throw new BusinessRuleViolation('You cannot change this booking.');
        }

        $date = Carbon::parse($data['booking_date'])->toDateString();
        $time = Carbon::parse($data['booking_time'])->format('H:i:s');

        if ($date < today()->toDateString()) {
            throw new BusinessRuleViolation('Choose today or a later date.');
        }

        if (! TimeSlots::isOpenAt($date, $time)) {
            throw new BusinessRuleViolation('The salon is not open at that date and time.');
        }

        $requestedStaff = BookingAccess::canReassign($actor)
            ? (isset($data['staff_id']) && $data['staff_id'] !== '' ? (int) $data['staff_id'] : null)
            : ($booking->staffID ? (int) $booking->staffID : null);

        $changes = DB::transaction(function () use ($booking, $date, $time, $requestedStaff, $data): array {
            $fresh = Booking::whereKey($booking->getKey())->lockForUpdate()->firstOrFail();

            if (! in_array($fresh->bookingStatus, BookingAccess::EDITABLE_STATUSES, true)) {
                throw new BusinessRuleViolation('Only pending or approved bookings can be changed.');
            }

            if ($fresh->bookingStatus === 'approved' && ! $requestedStaff) {
                throw new BusinessRuleViolation('An approved booking needs an assigned staff member.');
            }

            if ($requestedStaff) {
                $staff = Staff::whereKey($requestedStaff)->lockForUpdate()->first();

                if (! $staff || ! $staff->is_active || $staff->is_archived) {
                    throw new BusinessRuleViolation('Choose an active staff member.');
                }

                if (! $this->matcher->isAvailable($requestedStaff, $date, $time, (int) $fresh->serviceID, (int) $fresh->bookingID)) {
                    throw new BusinessRuleViolation('Slot no longer available for that staff member. Choose another time or person.');
                }
            }

            $scheduleChanged = $fresh->bookingDate->toDateString() !== $date
                || Carbon::parse((string) $fresh->bookingTime)->format('H:i:s') !== $time;
            $staffChanged = (int) $fresh->staffID !== (int) $requestedStaff;

            $fresh->update([
                'bookingDate' => $date,
                'bookingTime' => $time,
                'staffID' => $requestedStaff,
                'special_request' => $data['special_request'] ?? $fresh->special_request,
            ]);

            return ['booking' => $fresh, 'schedule' => $scheduleChanged, 'staff' => $staffChanged];
        });

        $this->tellPeople($changes, $date, $time);

        SystemLog::record($actor->role_slug, (int) $actor->getKey(), 'update_booking', "Updated booking {$booking->bookingRef}");
    }

    private function tellPeople(array $changes, string $date, string $time): void
    {
        /** @var Booking $booking */
        $booking = $changes['booking'];

        if ($changes['schedule'] && $booking->customer && ! $booking->is_walkin) {
            UserNotification::send(
                'customer',
                (int) $booking->customerID,
                'Booking Updated',
                "Your booking {$booking->bookingRef} is now on ".Carbon::parse($date)->format('F j, Y').' at '.Carbon::parse($time)->format('g:i A').'.'
            );
        }

        if ($changes['staff'] && $booking->staffID) {
            UserNotification::send('staff', (int) $booking->staffID, 'Booking Assigned', "Booking {$booking->bookingRef} was assigned to you.");
        }
    }
}
