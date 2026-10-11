<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;

class Home
{
    // Where each kind of user lands after logging in.
    public static function url(): string
    {
        if ($staff = Auth::guard('staff')->user()) {
            return route($staff->role_slug . '.dashboard');
        }

        if (Auth::guard('customer')->check()) {
            return route('customer.dashboard');
        }

        return route('login');
    }
}
