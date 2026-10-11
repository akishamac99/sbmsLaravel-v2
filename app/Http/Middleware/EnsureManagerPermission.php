<?php

namespace App\Http\Middleware;

use App\Models\Permission;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

// Use in routes as perm:bookings or perm:bookings,edit (the action defaults to view).
// Only Managers are checked against the permissions table. Owners and Staff pass through.
class EnsureManagerPermission
{
    public function handle(Request $request, Closure $next, string $module, string $action = 'view'): Response
    {
        $staff = Auth::guard('staff')->user();

        if ($staff && $staff->isManager() && ! Permission::allows('Manager', $module, $action)) {
            return redirect()->route('manager.dashboard')
                ->with('error', 'You do not have permission to access that section.');
        }

        return $next($request);
    }
}
