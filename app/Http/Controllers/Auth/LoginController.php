<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\SystemLog;
use App\Support\Home;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    public function show()
    {
        return view('auth.login');
    }

    // One login form for everyone: customers are tried first, then staff.
    public function store(Request $request)
    {
        // Postgres compares text exactly, so Maria@Gmail.com would not match maria@gmail.com.
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);

        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = $data['email'].'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()->withInput($request->only('email'))
                ->withErrors(['email' => "Too many attempts. Try again in {$seconds} seconds."]);
        }

        $credentials = ['email' => $data['email'], 'password' => $data['password']];

        // Customers must be active and verified.
        if (Auth::guard('customer')->attempt($credentials + ['accountStatus' => 'active', 'is_verified' => 1])) {
            Auth::guard('staff')->logout();

            return $this->finish($request, $throttleKey, 'customer', Auth::guard('customer')->id(), 'Customer logged in');
        }

        // Owner, managers and staff must be active.
        if (Auth::guard('staff')->attempt($credentials + ['is_active' => 1])) {
            Auth::guard('customer')->logout();
            $staff = Auth::guard('staff')->user();

            return $this->finish($request, $throttleKey, $staff->role_slug, $staff->getKey(), ucfirst($staff->role_slug).' logged in');
        }

        RateLimiter::hit($throttleKey);

        // Right password, but the account is switched off: say so instead of "invalid password".
        if (Auth::guard('customer')->validate($credentials)) {
            $status = Customer::where('email', $data['email'])->value('accountStatus');
            $message = match ($status) {
                'blocked' => 'This account has been blocked. Please contact the salon.',
                'inactive' => 'This account is inactive. Please contact the salon to reactivate it.',
                'pending' => 'Please finish verifying your email before logging in.',
                default => 'Invalid email or password',
            };

            return back()->withInput($request->only('email'))->withErrors(['email' => $message]);
        }

        return back()->withInput($request->only('email'))
            ->withErrors(['email' => 'Invalid email or password']);
    }

    public function destroy(Request $request)
    {
        if ($staff = Auth::guard('staff')->user()) {
            SystemLog::record($staff->role_slug, $staff->getKey(), 'logout', 'User logged out');
        } elseif ($customer = Auth::guard('customer')->user()) {
            SystemLog::record('customer', $customer->getKey(), 'logout', 'User logged out');
        }

        Auth::guard('customer')->logout();
        Auth::guard('staff')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function finish(Request $request, string $throttleKey, string $logType, int $id, string $details)
    {
        RateLimiter::clear($throttleKey);
        $request->session()->regenerate();
        SystemLog::record($logType, $id, 'login', $details);

        return redirect()->intended(Home::url());
    }
}
