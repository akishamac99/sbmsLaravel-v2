<?php

namespace App\Support;

use App\Models\SystemPolicy;
use Carbon\Carbon;
use Closure;

// The bookable times. The owner sets an opening and closing time for each weekday (Owner > Salon Hours).
// Slots start at opening time and repeat every 30 minutes, stopping before closing time.
class TimeSlots
{
    public const DAYS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

    public const POLICY_KEY = 'salon_hours';

    public const STEP = 30;

    private static ?array $hours = null;

    // Used until the owner saves their own hours.
    public static function defaultHours(): array
    {
        return array_fill_keys(self::DAYS, ['open' => '09:00', 'close' => '21:00']);
    }

    // ['Monday' => ['open' => '09:00', 'close' => '21:00'], 'Sunday' => null, ...]  (null = closed)
    public static function hours(): array
    {
        if (self::$hours !== null) {
            return self::$hours;
        }

        $saved = json_decode((string) SystemPolicy::value(self::POLICY_KEY, ''), true);

        if (! is_array($saved)) {
            return self::$hours = self::defaultHours();
        }

        $hours = [];

        foreach (self::DAYS as $day) {
            $row = $saved[$day] ?? null;
            $valid = is_array($row) && isset($row['open'], $row['close']) && $row['open'] < $row['close'];
            $hours[$day] = $valid ? ['open' => substr($row['open'], 0, 5), 'close' => substr($row['close'], 0, 5)] : null;
        }

        return self::$hours = $hours;
    }

    public static function save(array $hours): void
    {
        SystemPolicy::updateOrCreate(
            ['policy_key' => self::POLICY_KEY],
            ['policy_value' => json_encode($hours), 'description' => 'Salon opening and closing time for each weekday (JSON).']
        );

        self::$hours = null;
    }

    // ['09:00:00' => '9:00 AM', ...] for one weekday name. Empty when the salon is closed that day.
    public static function forDay(string $day): array
    {
        $window = self::hours()[$day] ?? null;

        if (! $window) {
            return [];
        }

        [$openH, $openM] = array_map('intval', explode(':', $window['open']));
        [$closeH, $closeM] = array_map('intval', explode(':', $window['close']));

        $slots = [];

        for ($minutes = $openH * 60 + $openM; $minutes < $closeH * 60 + $closeM; $minutes += self::STEP) {
            $time = sprintf('%02d:%02d:00', intdiv($minutes, 60), $minutes % 60);
            $slots[$time] = date('g:i A', strtotime($time));
        }

        return $slots;
    }

    // The slots on one calendar date (Y-m-d).
    public static function forDate(string $date): array
    {
        return self::forDay(Carbon::parse($date)->format('l'));
    }

    // Every slot that exists on any day of the week.
    public static function all(): array
    {
        $all = [];

        foreach (self::DAYS as $day) {
            $all += self::forDay($day);
        }

        ksort($all);

        return $all;
    }

    // Just the values, for validation: ['09:00:00', '09:30:00', ...]
    public static function values(): array
    {
        return array_keys(self::all());
    }

    public static function period(string $time): string
    {
        $hour = (int) substr($time, 0, 2);

        return $hour < 12 ? 'Morning' : ($hour < 18 ? 'Afternoon' : 'Evening');
    }

    // Slots split into Morning, Afternoon and Evening (for one date, or for the whole week).
    public static function groups(?string $date = null): array
    {
        $groups = ['Morning' => [], 'Afternoon' => [], 'Evening' => []];

        foreach (($date ? self::forDate($date) : self::all()) as $value => $label) {
            $groups[self::period($value)][$value] = $label;
        }

        return $groups;
    }

    public static function isOpenAt(string $date, string $time): bool
    {
        return isset(self::forDate($date)[Carbon::parse($time)->format('H:i:s')]);
    }

    // A validation rule: the chosen time must be a real slot on the chosen date.
    public static function openOn(?string $date): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($date): void {
            if (! $date || ! Carbon::hasFormat($date, 'Y-m-d')) {
                return; // the date rule reports its own error
            }

            if (! self::isOpenAt($date, (string) $value)) {
                $fail('The salon is not open at that time on the chosen date.');
            }
        };
    }

    // "Mon–Sat 9:00 AM – 9:00 PM · Sun Closed", for the landing page.
    public static function summary(): string
    {
        $format = fn (string $time): string => date('g:i A', strtotime($time));
        $runs = [];

        foreach (self::DAYS as $day) {
            $window = self::hours()[$day];
            $label = $window ? $format($window['open']).' – '.$format($window['close']) : 'Closed';
            $last = count($runs) - 1;

            if ($last >= 0 && $runs[$last]['label'] === $label) {
                $runs[$last]['to'] = $day;
            } else {
                $runs[] = ['from' => $day, 'to' => $day, 'label' => $label];
            }
        }

        return implode(' · ', array_map(function (array $run): string {
            $days = $run['from'] === $run['to']
                ? substr($run['from'], 0, 3)
                : substr($run['from'], 0, 3).'–'.substr($run['to'], 0, 3);

            return "{$days} {$run['label']}";
        }, $runs));
    }
}
