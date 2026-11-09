<?php

namespace App\Actions\Accounts;

use App\Exceptions\BusinessRuleViolation;
use App\Models\Customer;
use App\Models\Staff;
use App\Models\SystemLog;

/** UC53 Block / Unblock Customer. A blocked customer cannot book (see EnsureCustomerNotBlocked). */
final class SetCustomerBlockStatus
{
    public function block(Staff $actor, Customer $customer, ?string $reason): void
    {
        $this->assertRegistered($customer);

        if ($customer->accountStatus === 'blocked') {
            throw new BusinessRuleViolation('This customer is already blocked.');
        }

        $customer->update(['accountStatus' => 'blocked']);
        $this->log($actor, 'block_customer', "Blocked customer {$customer->getKey()}".($reason ? ": {$reason}" : ''));
    }

    public function unblock(Staff $actor, Customer $customer): void
    {
        $this->assertRegistered($customer);

        if ($customer->accountStatus !== 'blocked') {
            throw new BusinessRuleViolation('This customer is not blocked.');
        }

        // A customer who never finished email verification goes back to "pending", not "active".
        $customer->update(['accountStatus' => $customer->is_verified ? 'active' : 'pending']);
        $this->log($actor, 'unblock_customer', "Unblocked customer {$customer->getKey()}");
    }

    private function assertRegistered(Customer $customer): void
    {
        if ($customer->is_walkin) {
            throw new BusinessRuleViolation('Walk-in customers have no login, so they cannot be blocked.');
        }
    }

    private function log(Staff $actor, string $action, string $details): void
    {
        SystemLog::record($actor->role_slug, (int) $actor->getKey(), $action, $details);
    }
}
