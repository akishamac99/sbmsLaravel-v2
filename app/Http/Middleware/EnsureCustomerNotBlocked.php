<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** UC53: a blocked customer cannot book. Put it on the cart-checkout and checkout routes. */
class EnsureCustomerNotBlocked
{
    public function handle(Request $request, Closure $next): Response
    {
        $customer = Auth::guard('customer')->user();

        if ($customer && $customer->accountStatus === 'blocked') {
            return redirect()->route('customer.dashboard')
                ->with('error', 'Your account is blocked, so you cannot make a booking. Please contact the salon.');
        }

        return $next($request);
    }
}
