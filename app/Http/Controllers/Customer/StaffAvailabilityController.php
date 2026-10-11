<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\StaffMatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

// Replaces api/staff_availability.php: the booking form asks "who is free and qualified at this date and time?"
class StaffAvailabilityController extends Controller
{
    public function __invoke(Request $request, StaffMatcher $matcher): JsonResponse
    {
        $validator = Validator::make($request->query(), [
            'date' => ['required', 'date_format:Y-m-d'],
            'time' => ['required', 'date_format:H:i:s'],
            'service_id' => ['required', 'integer', 'min:1'],
            'booking' => ['nullable', 'string', 'max:30'],
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first(), 'staff' => []]);
        }

        // When rescheduling, the customer's own booking must not block its current slot.
        $excludeBookingId = $request->filled('booking')
            ? Booking::ownedBy((int) Auth::guard('customer')->id())->where('bookingRef', $request->query('booking'))->value('bookingID')
            : null;

        $staff = $matcher
            ->availableStaff($request->query('date'), $request->query('time'), (int) $request->query('service_id'), $excludeBookingId ? (int) $excludeBookingId : null)
            ->map(fn ($s) => [
                'staffID' => $s->staffID,
                'fullname' => $s->full_name,
                'specialization' => $s->specialization ?: 'General',
            ])
            ->values();

        return response()->json(['success' => true, 'staff' => $staff]);
    }
}
