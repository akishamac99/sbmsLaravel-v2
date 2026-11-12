<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Staff;
use App\Models\SystemLog;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

/**
 * Forgot password with a 6-digit code:
 *   1. enter your email            -> a code is emailed
 *   2. enter the code              -> checked (3 tries, 15 minutes)
 *   3. choose a new password       -> only reachable right after step 2
 * The answer is the same whether or not the email has an account, so the form cannot be used to find out who is registered.
 */
class PasswordResetController extends Controller
{
    private const SESSION_KEY = 'pw_reset';

    /** How long after a correct code the person may still choose the new password. */
    private const PASSWORD_WINDOW_MINUTES = 15;

    public function __construct(private readonly OtpService $otp)
    {
    }

    public function showForgot()
    {
        return view('auth.forgot-password');
    }

    public function sendCode(Request $request)
    {
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);
        $email = $request->validate(['email' => ['required', 'email', 'max:100']])['email'];

        $broker = $this->brokerFor($email);

        if ($broker) {
            $this->issueCode($email);
        }

        $request->session()->put(self::SESSION_KEY, ['email' => $email, 'broker' => $broker, 'verified_at' => null]);

        return redirect()->route('password.verify.show')
            ->with('status', 'If that email is registered, a 6-digit code has been sent. It is valid for '.OtpService::VALIDITY_MINUTES.' minutes.');
    }

    public function showVerify(Request $request)
    {
        $state = $request->session()->get(self::SESSION_KEY);

        if (! $state) {
            return redirect()->route('password.request');
        }

        return view('auth.reset-code', ['email' => $state['email']]);
    }

    public function verifyCode(Request $request)
    {
        $state = $request->session()->get(self::SESSION_KEY);

        if (! $state) {
            return redirect()->route('password.request')->withErrors(['email' => 'Please start again.']);
        }

        $request->validate(['otp_code' => ['required', 'digits:6']], ['otp_code.digits' => 'The code is 6 digits.']);

        $result = $this->otp->verify($state['email'], $request->input('otp_code'));

        if (! $result['success'] || ! $state['broker']) {
            return back()->withErrors(['otp_code' => $result['success'] ? 'Incorrect OTP. Please try again.' : $result['message']]);
        }

        $state['verified_at'] = now()->timestamp;
        $request->session()->put(self::SESSION_KEY, $state);
        $request->session()->regenerate();

        return redirect()->route('password.new');
    }

    public function resendCode(Request $request)
    {
        $state = $request->session()->get(self::SESSION_KEY);

        if (! $state) {
            return redirect()->route('password.request');
        }

        if ($state['broker']) {
            $this->issueCode($state['email']);
        }

        return back()->with('status', 'If that email is registered, a new code has been sent.');
    }

    public function showNew(Request $request)
    {
        if (! $this->isVerified($request)) {
            return redirect()->route('password.request')->with('error', 'Please verify the code first.');
        }

        return view('auth.reset-password', ['email' => $request->session()->get(self::SESSION_KEY.'.email')]);
    }

    public function saveNew(Request $request)
    {
        if (! $this->isVerified($request)) {
            return redirect()->route('password.request')->with('error', 'Your session expired. Please start again.');
        }

        $data = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $state = $request->session()->get(self::SESSION_KEY);
        $user = $state['broker'] === 'staff'
            ? Staff::where('email', $state['email'])->where('is_active', 1)->where('is_archived', 0)->first()
            : Customer::where('email', $state['email'])->where('is_walkin', 0)->first();

        if (! $user) {
            $request->session()->forget(self::SESSION_KEY);

            return redirect()->route('password.request')->with('error', 'That account is no longer available.');
        }

        $user->forceFill(['password' => $data['password']])->save();   // the model hashes it

        SystemLog::record(
            $state['broker'] === 'staff' ? $user->role_slug : 'customer',
            $user->getKey(),
            'reset_password',
            'Password reset with an emailed code'
        );

        $this->otp->clear($state['email']);
        $request->session()->forget(self::SESSION_KEY);

        return redirect()->route('login')->with('status', 'Password changed. Please log in with your new password.');
    }

    private function issueCode(string $email): void
    {
        $code = $this->otp->generate($email, 'reset');

        try {
            $this->otp->send($email, $code, 'password reset');
        } catch (Throwable $e) {
            // The reason is in storage/logs/laravel.log. The page still answers the same, so it cannot reveal which emails exist.
            report($e);
            $this->otp->clear($email);
        }
    }

    private function brokerFor(string $email): ?string
    {
        if (Customer::where('email', $email)->where('is_walkin', 0)->exists()) {
            return 'customers';
        }

        return Staff::where('email', $email)->where('is_active', 1)->where('is_archived', 0)->exists() ? 'staff' : null;
    }

    private function isVerified(Request $request): bool
    {
        $state = $request->session()->get(self::SESSION_KEY);

        return $state
            && $state['broker']
            && $state['verified_at']
            && (now()->timestamp - (int) $state['verified_at']) <= self::PASSWORD_WINDOW_MINUTES * 60;
    }
}
