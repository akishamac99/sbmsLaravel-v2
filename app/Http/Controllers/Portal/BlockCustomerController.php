<?php

namespace App\Http\Controllers\Portal;

use App\Actions\Accounts\SetCustomerBlockStatus;
use App\Exceptions\BusinessRuleViolation;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** UC53 Block / Unblock Customer. */
class BlockCustomerController extends Controller
{
    public function block(Request $request, Customer $customer, SetCustomerBlockStatus $status): RedirectResponse
    {
        $validated = $request->validate(['reason' => ['nullable', 'string', 'max:255']]);

        return $this->run(fn () => $status->block(Auth::guard('staff')->user(), $customer, $validated['reason'] ?? null), 'Customer blocked. They can no longer make bookings.');
    }

    public function unblock(Customer $customer, SetCustomerBlockStatus $status): RedirectResponse
    {
        return $this->run(fn () => $status->unblock(Auth::guard('staff')->user(), $customer), 'Customer unblocked.');
    }

    private function run(callable $operation, string $success): RedirectResponse
    {
        try {
            $operation();
        } catch (BusinessRuleViolation $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', $success);
    }
}
