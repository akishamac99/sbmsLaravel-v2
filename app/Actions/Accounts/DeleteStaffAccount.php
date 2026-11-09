<?php

namespace App\Actions\Accounts;

use App\Exceptions\BusinessRuleViolation;
use App\Models\Staff;
use App\Models\SystemLog;
use Illuminate\Support\Facades\DB;

/** UC05 Delete Account (staff, manager and co-owner accounts). Owner only. */
final class DeleteStaffAccount
{
    public function __construct(private readonly StaffDependencyCounter $dependencies)
    {
    }

    public function handle(Staff $actor, Staff $target, bool $dependenciesConfirmed): void
    {
        if (! $actor->isOwner()) {
            throw new BusinessRuleViolation('Only the Owner can delete user accounts.');
        }

        if ((int) $actor->getKey() === (int) $target->getKey()) {
            throw new BusinessRuleViolation('Cannot delete an account that is currently active.');
        }

        if ($target->isOwner()) {
            throw new BusinessRuleViolation('The Owner account cannot be deleted.');
        }

        // Business rule: active accounts cannot be deleted. Archive (or deactivate) first.
        if (! $target->is_archived) {
            throw new BusinessRuleViolation('Active accounts cannot be deleted. Archive the account first.');
        }

        $counts = $this->dependencies->for($target);

        if (($counts['bookings'] + $counts['feedback']) > 0 && ! $dependenciesConfirmed) {
            throw new BusinessRuleViolation('This account has existing bookings or feedback. Confirm that you understand the effect before deleting it.');
        }

        DB::transaction(fn () => $target->delete());

        SystemLog::record('owner', (int) $actor->getKey(), 'delete_account', "Deleted staff account {$target->username}");
    }
}
