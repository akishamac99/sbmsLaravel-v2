<?php

namespace App\Actions\Accounts;

use App\Exceptions\BusinessRuleViolation;
use App\Models\Staff;
use App\Models\SystemLog;

/** UC07 Restore Account. Changes an archived staff account back to Active; all data stays intact. Owner only. */
final class RestoreStaffAccount
{
    public function handle(Staff $actor, Staff $target): void
    {
        if (! $actor->isOwner()) {
            throw new BusinessRuleViolation('Only the Owner can restore accounts.');
        }

        if (! $target->is_archived) {
            throw new BusinessRuleViolation('Only archived accounts can be restored.');
        }

        $target->update(['is_archived' => 0, 'is_active' => 1]);

        SystemLog::record('owner', (int) $actor->getKey(), 'restore_account', "Restored staff account {$target->username}");
    }
}
