<?php

namespace App\Actions\Accounts;

use App\Models\Booking;
use App\Models\Feedback;
use App\Models\Staff;

/** Counts the records that depend on a staff account. Its only job is counting (UC05 pre-delete validation). */
final class StaffDependencyCounter
{
    /** @return array{active_bookings: int, bookings: int, feedback: int} */
    public function for(Staff $staff): array
    {
        $key = (int) $staff->getKey();

        return [
            'active_bookings' => Booking::where('staffID', $key)->whereIn('bookingStatus', ['pending', 'approved'])->count(),
            'bookings' => Booking::where('staffID', $key)->count(),
            'feedback' => Feedback::where('staffID', $key)->count(),
        ];
    }
}
