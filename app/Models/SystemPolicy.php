<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemPolicy extends Model
{
    protected $table = 'system_policies';

    public $timestamps = false;

    protected $fillable = ['policy_key', 'policy_value', 'description'];

    // Read one setting, e.g. SystemPolicy::value('downpayment_percent', 20)
    // All settings are read in one query and kept for 5 minutes (cleared whenever a setting is saved),
    // instead of one database query for every setting that a page reads.
    public static function value(string $key, mixed $default = null): mixed
    {
        return static::allValues()[$key] ?? $default;
    }

    /** @return array<string, mixed> */
    private static function allValues(): array
    {
        return Cache::remember('system_policies', 300, fn (): array => static::query()->pluck('policy_value', 'policy_key')->all());
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('system_policies'));
        static::deleted(fn () => Cache::forget('system_policies'));
    }

    /**
     * Hours before an appointment after which a customer can no longer cancel or reschedule it.
     */
    public static function cancellationWindowHours(): int
    {
        return once(fn (): int => max(0, (int) static::value('cancellation_window_hours', 24)));
    }

    /**
     * How many times a customer may move one booking to another date or time.
     */
    public static function maxReschedules(): int
    {
        return once(fn (): int => max(0, (int) static::value('max_reschedule_count', 2)));
    }
}
