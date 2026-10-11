<?php

namespace Database\Seeders;

use App\Models\Staff;
use App\Models\StaffAvailability;
use Illuminate\Database\Seeder;

// The starting schedule: every Staff and Manager account is available every day, all day.
// The owner can change anyone's hours afterwards from Staff Availability.
class StaffAvailabilitySeeder extends Seeder
{
    public function run(): void
    {
        $people = Staff::query()
            ->where('is_active', 1)
            ->where('is_archived', 0)
            ->whereIn('role', ['Staff', 'Manager'])
            ->get();

        foreach ($people as $person) {
            // Only fills days that have no schedule yet, so re-running never undoes hours the owner has set.
            StaffAvailability::openAllWeek((int) $person->getKey(), onlyMissing: true);
        }
    }
}
