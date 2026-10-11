<?php

namespace App\Http\Middleware;

use App\Support\Home;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

// Use in routes as role:manager, role:staff or role:owner.
// The owner may open any staff-side area. Anyone else is sent back to their own dashboard.
class EnsureRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $staff = Auth::guard('staff')->user();

        if (! $staff) {
            return redirect()->route('login');
        }

        if ($staff->role_slug !== strtolower($role) && ! $staff->isOwner()) {
            return redirect(Home::url());
        }

        return $next($request);
    }
}
