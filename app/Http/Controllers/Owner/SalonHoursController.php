<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\SystemLog;
use App\Support\TimeSlots;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SalonHoursController extends Controller
{
    public function edit(): View
    {
        // Shaped like staff availability rows, so the same weekly editor can show it.
        $schedule = collect(TimeSlots::hours())
            ->filter()
            ->map(fn (array $window) => (object) [
                'is_available' => 1,
                'start_time' => $window['open'].':00',
                'end_time' => $window['close'].':00',
            ]);

        return view('owner.salon-hours', ['days' => TimeSlots::DAYS, 'schedule' => $schedule]);
    }

    public function update(Request $request): RedirectResponse
    {
        $rules = ['availability' => ['nullable', 'array']];

        foreach (TimeSlots::DAYS as $day) {
            $rules["availability.{$day}.enabled"] = ['nullable', 'boolean'];
            $rules["availability.{$day}.start_time"] = ['nullable', 'date_format:H:i'];
            $rules["availability.{$day}.end_time"] = ['nullable', 'date_format:H:i'];
        }

        $validated = $request->validate($rules);
        $hours = [];

        foreach (TimeSlots::DAYS as $day) {
            $slot = $validated['availability'][$day] ?? [];

            if (empty($slot['enabled'])) {
                $hours[$day] = null;
                continue;
            }

            if (empty($slot['start_time']) || empty($slot['end_time']) || $slot['start_time'] >= $slot['end_time']) {
                throw ValidationException::withMessages([
                    "availability.{$day}.end_time" => "Choose a closing time later than the opening time for {$day}.",
                ]);
            }

            $hours[$day] = ['open' => $slot['start_time'], 'close' => $slot['end_time']];
        }

        if (! array_filter($hours)) {
            throw ValidationException::withMessages(['availability' => 'The salon must be open on at least one day.']);
        }

        TimeSlots::save($hours);
        SystemLog::record('owner', (int) Auth::guard('staff')->id(), 'update_salon_hours', 'Updated the salon opening hours');

        $outside = $this->bookingsOutside($hours);

        return back()->with('status', 'Salon hours saved.'.($outside > 0
            ? " {$outside} upcoming booking(s) fall outside the new hours. They are not cancelled, so please review them under Manage Bookings."
            : ''));
    }

    /** Upcoming pending or approved bookings that the new hours would not allow. */
    private function bookingsOutside(array $hours): int
    {
        return Booking::whereIn('bookingStatus', ['pending', 'approved'])
            ->whereDate('bookingDate', '>=', Carbon::today()->toDateString())
            ->get(['bookingDate', 'bookingTime'])
            ->filter(function ($booking) use ($hours): bool {
                $window = $hours[$booking->bookingDate->format('l')] ?? null;
                $time = substr((string) $booking->bookingTime, 0, 5);

                return ! $window || $time < $window['open'] || $time >= $window['close'];
            })
            ->count();
    }
}
