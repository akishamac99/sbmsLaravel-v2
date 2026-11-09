<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemPolicy extends Model
{
    protected $table = 'system_policies';

    public $timestamps = false;

    protected $fillable = ['policy_key', 'policy_value', 'description'];

    // Read one setting, e.g. SystemPolicy::value('downpayment_percent', 20)
    public static function value(string $key, mixed $default = null): mixed
    {
        return static::where('policy_key', $key)->value('policy_value') ?? $default;
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
