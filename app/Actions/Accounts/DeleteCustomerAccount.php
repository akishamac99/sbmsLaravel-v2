<?php

namespace App\Actions\Accounts;

use App\Exceptions\BusinessRuleViolation;
use App\Models\Customer;
use App\Models\Staff;
use App\Models\SystemLog;
use Illuminate\Support\Facades\DB;

/**
 * UC05 Delete Account (customer accounts). Owner only.
 * Booking rows are removed by the database when a customer is removed, so a customer
 * that has any booking history is refused: it stays inactive instead.
 */
final class DeleteCustomerAccount
{
    public function handle(Staff $actor, Customer $target): void
    {
        if (! $actor->isOwner()) {
            throw new BusinessRuleViolation('Only the Owner can delete user accounts.');
        }

        if ($target->is_walkin) {
            throw new BusinessRuleViolation('Walk-in customer records are part of booking history and cannot be deleted.');
        }

        if ($target->accountStatus === 'active') {
            throw new BusinessRuleViolation('Active accounts cannot be deleted. Set the account to inactive first.');
        }

        if ($target->bookings()->exists()) {
            throw new BusinessRuleViolation('This account has booking records. Keep it inactive to preserve the booking history.');
        }

        DB::transaction(fn () => $target->delete());

        SystemLog::record('owner', (int) $actor->getKey(), 'delete_account', "Deleted customer account {$target->getKey()}");
    }
}
