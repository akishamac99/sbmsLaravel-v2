<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Staff;
use App\Models\SystemLog;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    public function showForgot()
    {
        return view('auth.forgot-password');
    }

    public function sendLink(Request $request)
    {
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);

        $email = $request->validate(['email' => ['required', 'email']])['email'];

        // Work out which kind of account this email belongs to.
        $broker = Customer::where('email', $email)->exists()
            ? 'customers'
            : (Staff::where('email', $email)->where('is_active', 1)->exists() ? 'staff' : null);

        if ($broker) {
            try {
                Password::broker($broker)->sendResetLink(['email' => $email]);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        // Same answer whether or not the email exists, so nobody can use this form to find out who has an account.
        return back()->with('status', 'If that email is registered, a password reset link has been sent.');
    }

    public function showReset(Request $request, string $broker, string $token)
    {
        abort_unless(in_array($broker, ['customers', 'staff'], true), 404);

        return view('auth.reset-password', [
            'broker' => $broker,
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function reset(Request $request, string $broker)
    {
        abort_unless(in_array($broker, ['customers', 'staff'], true), 404);

        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);

        $data = $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $status = Password::broker($broker)->reset($data, function ($user, $password) use ($broker) {
            $user->forceFill(['password' => $password])->save();   // the model hashes it
            event(new PasswordReset($user));

            SystemLog::record(
                $broker === 'staff' ? $user->role_slug : 'customer',
                $user->getKey(),
                'reset_password',
                'Password reset via email link'
            );
        });

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')
                ->with('status', 'Password reset successfully. Please log in with your new password.');
        }

        return back()->withInput($request->only('email'))
            ->withErrors(['email' => 'Invalid or expired reset link.']);
    }
}
