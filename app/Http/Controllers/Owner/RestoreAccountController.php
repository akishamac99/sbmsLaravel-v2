<?php

namespace App\Http\Controllers\Owner;

use App\Actions\Accounts\RestoreStaffAccount;
use App\Exceptions\BusinessRuleViolation;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Staff;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/** UC07 Restore Account, plus the archived-accounts list it starts from. */
class RestoreAccountController extends Controller
{
    public function archived(): View
    {
        return view('owner.archived-accounts', [
            'archivedStaff' => Staff::where('is_archived', 1)->orderBy('firstName')->get(),
            'removableCustomers' => Customer::where('is_walkin', 0)->whereIn('accountStatus', ['inactive', 'blocked'])->orderBy('firstName')->get(),
        ]);
    }

    public function confirm(Staff $staff): View
    {
        abort_unless($staff->is_archived, 404);

        return view('owner.restore-account', ['staff' => $staff]);
    }

    public function restore(Staff $staff, RestoreStaffAccount $restore): RedirectResponse
    {
        try {
            $restore->handle(Auth::guard('staff')->user(), $staff);
        } catch (BusinessRuleViolation $e) {
            return redirect()->route('owner.accounts.archived')->with('error', $e->getMessage());
        }

        return redirect()->route('owner.accounts.archived')->with('status', 'Account successfully restored');
    }
}
