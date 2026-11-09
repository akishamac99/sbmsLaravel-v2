<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Staff;
use App\Models\StaffAvailability;
use App\Models\StaffUnavailability;
use App\Support\TimeSlots;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * View Available and Reserved Schedule.
 * One day, every time slot, and for each staff member whether the slot is open, reserved or outside their hours.
 * Salon Staff see only their own column; the Owner and Head Manager see everyone.
 * A fixed number of queries, however many slots there are.
 */
class ViewScheduleController extends Controller
{
    private const FREED = ['declined', 'cancelled', 'no-show'];

    public function __invoke(Request $request): View
    {
        $validated = $request->validate(['date' => ['nullable', 'date_format:Y-m-d']]);

        $user = Auth::guard('staff')->user();
        $date = $validated['date'] ?? today()->toDateString();
        $day = Carbon::parse($date)->format('l');

        $team = Staff::query()
            ->where('is_active', 1)
            ->where('is_archived', 0)
            ->whereIn('role', ['Owner', 'Manager', 'Staff'])
            ->when($user->role_slug === 'staff', fn ($q) => $q->whereKey($user->getKey()))
            ->orderBy('firstName')
            ->get();

        $ids = $team->pluck('staffID');

        $hours = StaffAvailability::whereIn('staffID', $ids)->where('day_of_week', $day)->where('is_available', 1)->get()->groupBy('staffID');
        $daysOff = StaffUnavailability::whereIn('staffID', $ids)->whereDate('unavailable_date', $date)->pluck('staffID')->flip();
        $booked = Booking::with(['customer', 'serviceItem'])
            ->whereIn('staffID', $ids)
            ->whereDate('bookingDate', $date)
            ->whereNotIn('bookingStatus', self::FREED)
            ->get()
            ->groupBy(fn (Booking $b) => $b->staffID.'|'.Carbon::parse((string) $b->bookingTime)->format('H:i:s'));

        $rows = [];

        foreach (TimeSlots::forDate($date) as $time => $label) {
            $cells = [];

            foreach ($team as $member) {
                $reserved = $booked->get($member->getKey().'|'.$time);
                $inHours = isset($hours[$member->getKey()]) && $hours[$member->getKey()]->contains(
                    fn ($h) => Carbon::parse((string) $h->start_time)->format('H:i:s') <= $time && Carbon::parse((string) $h->end_time)->format('H:i:s') > $time
                );

                $cells[$member->getKey()] = $reserved
                    ? ['state' => 'reserved', 'booking' => $reserved->first()]
                    : (($inHours && ! isset($daysOff[$member->getKey()])) ? ['state' => 'available'] : ['state' => 'off']);
            }

            $rows[] = ['label' => $label, 'cells' => $cells];
        }

        return view('portal.schedule', [
            'role' => $user->role_slug,
            'date' => $date,
            'team' => $team,
            'rows' => $rows,
        ]);
    }
}
