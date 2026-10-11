<?php

namespace App\Console\Commands;

use App\Models\Staff;
use App\Models\StaffAvailability;
use Illuminate\Console\Command;

class OpenStaffSchedules extends Command
{
    protected $signature = 'salon:open-schedules {--only-missing : Keep days that already have a schedule and only add the missing days}';

    protected $description = 'Make every active Staff and Manager account available every day, all day';

    public function handle(): int
    {
        $onlyMissing = (bool) $this->option('only-missing');

        $people = Staff::query()
            ->where('is_active', 1)
            ->where('is_archived', 0)
            ->whereIn('role', ['Staff', 'Manager'])
            ->orderBy('staffID')
            ->get();

        if ($people->isEmpty()) {
            $this->warn('No active Staff or Manager accounts found. Run php artisan db:seed first.');

            return self::SUCCESS;
        }

        $rows = [];

        foreach ($people as $person) {
            $rows[] = [
                $person->staffID,
                $person->full_name,
                $person->role,
                StaffAvailability::openAllWeek((int) $person->getKey(), $onlyMissing),
            ];
        }

        $this->table(['ID', 'Name', 'Role', 'Days added or changed'], $rows);
        $this->info($onlyMissing
            ? 'Done. Existing schedules were kept; missing days were added.'
            : 'Done. Everyone is now available every day, all day.');

        return self::SUCCESS;
    }
}
