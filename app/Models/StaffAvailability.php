<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffAvailability extends Model
{
    protected $table = 'staff_availability';
    protected $primaryKey = 'availID';

        public const WEEK = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

    // "Available all day": 12:00 AM to 11:59 PM.
    public const OPEN_FROM = '00:00:00';
    public const OPEN_UNTIL = '23:59:00';

    // Make a staff member available every day of the week, all day.
    // Safe to run again: an existing day is updated, never duplicated.
    // With $onlyMissing, days that already have a schedule are left alone.
    public static function openAllWeek(int $staffId, bool $onlyMissing = false): int
    {
        $changed = 0;

        foreach (self::WEEK as $day) {
            $row = static::firstOrNew(['staffID' => $staffId, 'day_of_week' => $day]);

            if ($row->exists && $onlyMissing) {
                continue;
            }

            $row->fill([
                'start_time' => self::OPEN_FROM,
                'end_time' => self::OPEN_UNTIL,
                'is_available' => 1,
            ]);

            if (! $row->exists || $row->isDirty()) {
                $row->save();
                $changed++;
            }
        }

        return $changed;
    }

    public $timestamps = false;

    protected $guarded = ['availID'];

    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staffID', 'staffID');
    }
}
