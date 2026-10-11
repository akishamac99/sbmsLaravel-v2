<?php

namespace App\Support;

// The bookable times: 9:00 AM to 9:00 PM, every 30 minutes.
class TimeSlots
{
    // ['09:00:00' => '9:00 AM', '09:30:00' => '9:30 AM', ...]
    public static function all(): array
    {
        $slots = [];

        for ($minutes = 9 * 60; $minutes <= 21 * 60; $minutes += 30) {
            $time = sprintf('%02d:%02d:00', intdiv($minutes, 60), $minutes % 60);
            $slots[$time] = date('g:i A', strtotime($time));
        }

        return $slots;
    }

    // Just the values, for validation: ['09:00:00', '09:30:00', ...]
    public static function values(): array
    {
        return array_keys(self::all());
    }

    // The same slots split for the time picker: Morning, Afternoon and Evening.
    public static function groups(): array
    {
        $groups = ['Morning' => [], 'Afternoon' => [], 'Evening' => []];

        foreach (self::all() as $value => $label) {
            $hour = (int) substr($value, 0, 2);
            $group = $hour < 12 ? 'Morning' : ($hour < 18 ? 'Afternoon' : 'Evening');
            $groups[$group][$value] = $label;
        }

        return $groups;
    }
}
