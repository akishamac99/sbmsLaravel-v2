<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\SystemLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(): View
    {
        return view('customer.profile', [
            'customer' => Customer::findOrFail(Auth::guard('customer')->id()),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $customer = Customer::findOrFail(Auth::guard('customer')->id());

        // Email is not editable here: it was verified by OTP at registration.
        $validated = $request->validate([
            'firstName'     => ['required', 'string', 'max:50'],
            'middleName'    => ['nullable', 'string', 'max:50'],
            'lastName'      => ['required', 'string', 'max:50'],
            'contactNumber' => ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s()]{7,20}$/',
                                'unique:customer,contactNumber,' . $customer->getKey() . ',customerID'],
            'home_address'  => ['nullable', 'string', 'max:500'],
        ], [
            'contactNumber.regex'  => 'Enter a valid contact number.',
            'contactNumber.unique' => 'That contact number is already used by another account.',
        ]);

        $customer->update($validated);
        SystemLog::record('customer', (int) $customer->getKey(), 'update_profile', 'Updated profile details');

        return back()->with('status', 'Profile updated.');
    }

    public function password(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('password', [
            'current_password' => ['required', 'current_password:customer'],
            'password'         => ['required', 'string', 'min:8', 'confirmed', 'different:current_password'],
        ]);

        $customer = Customer::findOrFail(Auth::guard('customer')->id());
        $customer->update(['password' => $validated['password']]);
        SystemLog::record('customer', (int) $customer->getKey(), 'change_password', 'Changed account password');

        return back()->with('status', 'Password updated.');
    }
}