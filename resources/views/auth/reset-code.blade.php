@extends('layouts.auth')
@section('title', 'Enter Code')
@section('hero')
    <h2>Check<br><em>your</em><br>email.</h2>
    <p>We sent a 6-digit code. It works for {{ \App\Services\OtpService::VALIDITY_MINUTES }} minutes and you have {{ \App\Services\OtpService::MAX_ATTEMPTS }} tries.</p>
@endsection
@section('heading', 'Enter the code')
@section('subheading', 'Step 2 of 3: sent to ' . $email)

@section('content')
    @if ($errors->any())
        <div class="alert alert-err" role="alert"><i class="fas fa-exclamation-circle" aria-hidden="true"></i> {{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('password.verify') }}">
        @csrf
        <div class="field otp">
            <label for="fp-code">Verification code</label>
            <input id="fp-code" name="otp_code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" placeholder="000000" required autofocus>
            <small>Check your spam folder if it does not arrive within a minute.</small>
        </div>
        <button class="btn" type="submit">Verify code</button>
    </form>
    <form method="POST" action="{{ route('password.resend') }}">
        @csrf
        <button class="btn btn-ghost" type="submit">Send a new code</button>
    </form>

    <div class="links"><a href="{{ route('password.request') }}">Use a different email</a><a href="{{ route('login') }}">Back to sign in</a></div>
@endsection
