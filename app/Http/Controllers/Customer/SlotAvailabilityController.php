<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\StaffMatcher;
use App\Support\TimeSlots;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

// The booking screen asks: for this date and service, which time slots are open, nearly full, full or passed?
class SlotAvailabilityController extends Controller
{
    public function __invoke(Request $request, StaffMatcher $matcher): JsonResponse
    {
        $validator = Validator::make($request->query(), [
            'date' => ['required', 'date_format:Y-m-d'],
            'service_id' => ['required', 'integer', 'min:1'],
            'booking' => ['nullable', 'string', 'max:30'],
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first(), 'slots' => []]);
        }

        $date = $request->query('date');

        // When rescheduling, the customer's own booking must not block its current slot.
        $excludeBookingId = $request->filled('booking')
            ? Booking::ownedBy((int) Auth::guard('customer')->id())->where('bookingRef', $request->query('booking'))->value('bookingID')
            : null;

        $counts = $matcher->slotCounts($date, (int) $request->query('service_id'), $excludeBookingId ? (int) $excludeBookingId : null);
        $now = now();

        $slots = collect(TimeSlots::forDate($date))
            ->map(function (string $label, string $time) use ($counts, $date, $now): array {
                $count = $counts[$time] ?? 0;
                $past = Carbon::parse("{$date} {$time}")->lte($now);

                return [
                    'value' => $time,
                    'label' => $label,
                    'period' => TimeSlots::period($time),
                    'count' => $count,
                    'state' => $past ? 'past' : ($count === 0 ? 'full' : ($count === 1 ? 'few' : 'open')),
                ];
            })
            ->values();

        return response()->json(['success' => true, 'closed' => $slots->isEmpty(), 'slots' => $slots]);
    }
}
