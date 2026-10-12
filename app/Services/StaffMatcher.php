<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Service;
use App\Models\Staff;
use App\Models\StaffAvailability;
use App\Models\StaffUnavailability;
use Carbon\Carbon;
use App\Support\TimeSlots;
use Illuminate\Database\Eloquent\Collection;

// Decides which staff member can do which service, and who is free at a given date and time.
// Ported from SalonSystem::isStaffQualifiedForService(), isStaffAvailable() and getAvailableStaffForSlot().
class StaffMatcher
{
    // A booking in one of these states no longer blocks a staff member's time slot.
    private const FREED_STATUSES = ['cancelled', 'declined', 'completed', 'no-show'];

    /* ------------------------------------------------------------------
     | Qualification: does this staff member's specialization fit the service?
     ------------------------------------------------------------------ */

    // What kind of work a service is (barber, hair, makeup, ...), guessed from its words.
    public static function serviceTags(?string $name, ?string $category, ?string $description): array
    {
        $text = strtolower(trim(implode(' ', [$name, $category, $description])));
        // "Hair removal" describes waxing, not hair styling, so it must not make a waxing service count as a hair service.
        $text = preg_replace('/hair[\s-]+removal/', ' ', $text);
        $has = fn (string ...$words) => (bool) array_filter($words, fn ($w) => str_contains($text, $w));

        $tags = [];

        // "men" must be a whole word. Otherwise "women" would count as a men's service.
        if ($has('barber') || (preg_match('/\bmen\b/', $text) && $has('hair cut', 'haircut'))) {
            $tags[] = 'barber';
        }
        if ($has('hair', 'rebond', 'keratin', 'cellophane', 'celloshine', 'highlights')) {
            $tags[] = 'hair';
        }
        if ($has('makeup', 'make up', 'wedding', 'prenup', 'debut', 'pageant')) {
            $tags[] = 'makeup';
        }
        if ($has('massage')) {
            $tags[] = 'massage';
        }
        if ($has('facial', 'skin', 'cleansing', 'anti-aging')) {
            $tags[] = 'facial';
        }
        if ($has('wax')) {
            $tags[] = 'waxing';
        }
        if ($has('nail', 'manicure', 'pedicure')) {
            $tags[] = 'nails';
        }

        if ($has('lash')) {
            $tags[] = 'lash';
        }
        if ($has('brow')) {
            $tags[] = 'brows';
        }

        return $tags;
    }

    // What a staff member is trained for, read from their specialization text.
    // ['all'] means an all-around staff member who can do any service.
    public static function staffTags(?string $specialization): array
    {
        $spec = trim(strtolower((string) $specialization));

        if ($spec === '' || $spec === 'general' || str_contains($spec, 'all-around') || str_contains($spec, 'all around')) {
            return ['all'];
        }

        $has = fn (string ...$words) => (bool) array_filter($words, fn ($w) => str_contains($spec, $w));
        $tags = [];

        if ($has('barber')) {
            $tags[] = 'barber';
        }
        if ($has('hair', 'stylist')) {
            $tags[] = 'hair';
        }
        if ($has('makeup', 'make up', 'beautician')) {
            $tags[] = 'makeup';
        }
        if ($has('massage', 'therapist')) {
            $tags[] = 'massage';
        }
        if ($has('facial', 'skin', 'esthetic')) {
            $tags[] = 'facial';
        }
        if ($has('wax')) {
            $tags[] = 'waxing';
        }
        if ($has('nail', 'manicure', 'pedicure')) {
            $tags[] = 'nails';
        }

        return $tags;
    }

    // The pure rule: can someone with this role and specialization do this service?
    public static function qualifies(?string $role, ?string $specialization, ?string $name, ?string $category, ?string $description): bool
    {
        if (! in_array($role, ['Owner','Staff', 'Manager'], true)) {
            return false;
        }

        if ($role === 'Owner') {
            return true;
        }

        $serviceTags = self::serviceTags($name, $category, $description);
        $staffTags = self::staffTags($specialization);

        if (in_array('all', $staffTags, true)) {
            return $role === 'Staff';
        }

        if ($serviceTags && $staffTags) {
            return (bool) array_intersect($serviceTags, $staffTags);
        }

        // Fallback: look for a meaningful word of the specialization inside the service text.
        $haystack = strtolower(implode(' ', [$name, $category, $description]));
        $synonyms = [
            'make' => 'makeup', 'up' => 'makeup', 'artist' => '', 'tech' => '',
            'beautician' => '', 'stylist' => '', 'barber' => 'hair', 'barbering' => 'hair',
        ];

        foreach (preg_split('/[^a-z0-9]+/', trim(strtolower((string) $specialization))) as $token) {
            $token = $synonyms[$token] ?? $token;

            // Skip the generic word "services", which appears in almost every specialization and category name.
            if ($token !== '' && ! in_array($token, ['services', 'service'], true) && strlen($token) >= 4 && str_contains($haystack, $token)) {
                return true;
            }
        }

        return false;
    }

    public function isQualified(Staff $staff, Service $service): bool
    {
        return self::qualifies(
            $staff->role,
            $staff->specialization,
            $service->serviceName,
            $service->category?->category_name,
            $service->description
        );
    }

    /* ------------------------------------------------------------------
     | Availability: is the person free on that date and time?
     ------------------------------------------------------------------ */

    public function isAvailable(?int $staffId, ?string $date, ?string $time, ?int $serviceId = null, ?int $excludeBookingId = null): bool
    {
        if (! $staffId || ! $date || ! $time) {
            return true;
        }

        $staff = Staff::find($staffId);

        if (! $staff) {
            return false;
        }

        if ($serviceId) {
            $service = Service::with('category')->find($serviceId);

            if (! $service || ! $this->isQualified($staff, $service)) {
                return false;
            }
        }

        $time = $this->normalizeTime($time);
        $day = Carbon::parse($date)->format('l');

        // Must work that weekday, and the time must fall inside the working hours.
        $works = StaffAvailability::where('staffID', $staffId)
            ->where('day_of_week', $day)
            ->where('is_available', 1)
            ->where('start_time', '<=', $time)
            ->where('end_time', '>', $time)
            ->exists();

        if (! $works) {
            return false;
        }

        // Must not have a day off on that date.
        if (StaffUnavailability::where('staffID', $staffId)->whereDate('unavailable_date', $date)->exists()) {
            return false;
        }

        // Must not already have a booking at that exact time.
        return ! Booking::where('staffID', $staffId)
            ->whereDate('bookingDate', $date)
            ->where('bookingTime', $time)
            ->whereNotIn('bookingStatus', self::FREED_STATUSES)
            ->when($excludeBookingId, fn ($q) => $q->where('bookingID', '<>', $excludeBookingId))
            ->exists();
    }

    // Everyone who could take this slot (and, if a service is given, is qualified for it).
    public function availableStaff(string $date, string $time, ?int $serviceId = null, ?int $excludeBookingId = null): Collection
    {
        $time = $this->normalizeTime($time);
        $day = Carbon::parse($date)->format('l');

        $staff = Staff::query()
            ->where('is_active', 1)
            ->where('is_archived', 0)
            ->whereIn('role', ['Owner', 'Staff', 'Manager'])
            ->whereHas('availability', fn ($q) => $q
                ->where('day_of_week', $day)
                ->where('is_available', 1)
                ->where('start_time', '<=', $time)
                ->where('end_time', '>', $time))
            ->whereDoesntHave('unavailability', fn ($q) => $q->whereDate('unavailable_date', $date))
            ->whereDoesntHave('bookings', fn ($q) => $q
                ->whereDate('bookingDate', $date)
                ->where('bookingTime', $time)
                ->whereNotIn('bookingStatus', self::FREED_STATUSES)
                ->when($excludeBookingId, fn ($q2) => $q2->where('bookingID', '<>', $excludeBookingId)))
            ->orderBy('firstName')
            ->get();

        if (! $serviceId) {
            return $staff;
        }

        $service = Service::with('category')->find($serviceId);

        return $service
            ? $staff->filter(fn (Staff $s) => $this->isQualified($s, $service))->values()
            : $staff->take(0);
    }

    // For the booking screen: how many people could take each time slot on this date?
    // ['09:00:00' => 2, '09:30:00' => 0, ...]. A few queries in total, not one per slot.
    public function slotCounts(string $date, ?int $serviceId = null, ?int $excludeBookingId = null): array
    {
        $slots = TimeSlots::forDate($date);

        if ($slots === []) {
            return [];
        }

        $day = Carbon::parse($date)->format('l');

        $staff = Staff::query()
            ->where('is_active', 1)
            ->where('is_archived', 0)
            ->whereIn('role', ['Owner', 'Staff', 'Manager'])
            ->whereHas('availability', fn ($q) => $q->where('day_of_week', $day)->where('is_available', 1))
            ->whereDoesntHave('unavailability', fn ($q) => $q->whereDate('unavailable_date', $date))
            ->with(['availability' => fn ($q) => $q->where('day_of_week', $day)->where('is_available', 1)])
            ->get();

        if ($serviceId) {
            $service = Service::with('category')->find($serviceId);
            $staff = $service ? $staff->filter(fn (Staff $s) => $this->isQualified($s, $service))->values() : $staff->take(0);
        }

        $taken = Booking::whereDate('bookingDate', $date)
            ->whereIn('staffID', $staff->pluck('staffID')->values()->all())
            ->whereNotIn('bookingStatus', self::FREED_STATUSES)
            ->when($excludeBookingId, fn ($q) => $q->where('bookingID', '<>', $excludeBookingId))
            ->get(['staffID', 'bookingTime'])
            ->groupBy('staffID')
            ->map(fn ($rows) => $rows->map(fn ($row) => $this->normalizeTime((string) $row->bookingTime))->all());

        $counts = [];

        foreach ($slots as $time => $label) {
            $counts[$time] = $staff->filter(function (Staff $s) use ($time, $taken): bool {
                $window = $s->availability->first();

                return $window
                    && $this->normalizeTime((string) $window->start_time) <= $time
                    && $this->normalizeTime((string) $window->end_time) > $time
                    && ! in_array($time, $taken->get($s->getKey(), []), true);
            })->count();
        }

        return $counts;
    }

    // "9:00", "09:00" and "09:00:00" all become "09:00:00".
    private function normalizeTime(string $time): string
    {
        return Carbon::parse($time)->format('H:i:s');
    }
}
