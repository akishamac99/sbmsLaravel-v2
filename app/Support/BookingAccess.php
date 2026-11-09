<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\Permission;
use App\Models\Staff;

/**
 * Who may change a booking or record a payment. One small class, so every screen and action
 * ask the same question and get the same answer.
 *  - Owner: everything.
 *  - Manager: what the Owner allows in Permissions (bookings / payments).
 *  - Salon Staff: may edit only bookings assigned to them, and may not change who is assigned.
 */
final class BookingAccess
{
    public const EDITABLE_STATUSES = ['pending', 'approved'];

    public static function canEdit(Staff $user, Booking $booking): bool
    {
        if (! in_array($booking->bookingStatus, self::EDITABLE_STATUSES, true)) {
            return false;
        }

        return match ($user->role_slug) {
            'owner' => true,
            'manager' => Permission::allows('Manager', 'bookings', 'edit'),
            'staff' => (int) $booking->staffID === (int) $user->getKey(),
            default => false,
        };
    }

    public static function canReassign(Staff $user): bool
    {
        return $user->isOwner() || ($user->role_slug === 'manager' && Permission::allows('Manager', 'bookings', 'edit'));
    }

    public static function canRecordPayment(Staff $user): bool
    {
        return $user->isOwner() || ($user->role_slug === 'manager' && Permission::allows('Manager', 'payments', 'edit'));
    }
}
