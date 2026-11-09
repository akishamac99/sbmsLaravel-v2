<?php

namespace App\Http\Controllers\Owner;

use App\Actions\Accounts\DeleteCustomerAccount;
use App\Actions\Accounts\DeleteStaffAccount;
use App\Actions\Accounts\StaffDependencyCounter;
use App\Exceptions\BusinessRuleViolation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\ConfirmDeleteAccountRequest;
use App\Models\Customer;
use App\Models\Staff;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/** UC05 Delete Account. Shows the confirmation prompt, then deletes. */
class DeleteAccountController extends Controller
{
    public function confirmStaff(Staff $staff, StaffDependencyCounter $dependencies): View
    {
        return view('owner.delete-account', [
            'accountName' => $staff->full_name,
            'accountLabel' => "{$staff->role} account ({$staff->username})",
            'counts' => $dependencies->for($staff),
            'action' => route('owner.accounts.staff.destroy', $staff),
        ]);
    }

    public function destroyStaff(ConfirmDeleteAccountRequest $request, Staff $staff, DeleteStaffAccount $delete): RedirectResponse
    {
        try {
            $delete->handle(Auth::guard('staff')->user(), $staff, $request->boolean('confirm_dependencies'));
        } catch (BusinessRuleViolation $e) {
            return back()->with('error', $e->getMessage());
        } catch (QueryException $e) {
            report($e);

            return back()->with('error', 'The account could not be deleted because other records still depend on it. Keep it archived instead.');
        }

        return redirect()->route('owner.accounts.archived')->with('status', 'Account successfully deleted.');
    }

    public function confirmCustomer(Customer $customer): View
    {
        return view('owner.delete-account', [
            'accountName' => $customer->full_name,
            'accountLabel' => "customer account ({$customer->email})",
            'counts' => null,
            'action' => route('owner.accounts.customer.destroy', $customer),
        ]);
    }

    public function destroyCustomer(ConfirmDeleteAccountRequest $request, Customer $customer, DeleteCustomerAccount $delete): RedirectResponse
    {
        try {
            $delete->handle(Auth::guard('staff')->user(), $customer);
        } catch (BusinessRuleViolation $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('owner.accounts.archived')->with('status', 'Account successfully deleted.');
    }
}
