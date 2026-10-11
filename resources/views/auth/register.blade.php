@extends('layouts.auth')
@section('title', 'Register')
@section('hero')
    <h2>Join<br><em>our</em><br>salon family.</h2>
    <p>Create a customer account to book services, track appointments and manage payments.</p>
@endsection
@section('heading', $step === 1 ? 'Create account' : 'Verify your email')
@section('subheading', $step === 1 ? 'Fill in your details to get started' : "Enter the 6-digit code we sent to {$email}")

@section('content')
    @if ($errors->any() && $step === 2)
        <div class="alert alert-err"><i class="fas fa-exclamation-circle"></i> {{ $errors->first() }}</div>
    @endif

    @if ($step === 1)
        <form method="POST" action="{{ route('register.send-otp') }}">
            @csrf
            <div class="row2">
                <div class="field">
                    <label>First name</label>
                    <input name="first_name" value="{{ old('first_name') }}" required>
                    @error('first_name')<div class="err">{{ $message }}</div>@enderror
                </div>
                <div class="field">
                    <label>Last name</label>
                    <input name="last_name" value="{{ old('last_name') }}" required>
                    @error('last_name')<div class="err">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="field">
                <label>Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required>
                @error('email')<div class="err">{{ $message }}</div>@enderror
            </div>
            <div class="field">
                <label>Contact number</label>
                <input name="contact_number" value="{{ old('contact_number') }}" required>
                @error('contact_number')<div class="err">{{ $message }}</div>@enderror
            </div>
            <div class="row2">
                <div class="field">
                    <label>Password</label>
                    <input type="password" name="password" required>
                    @error('password')<div class="err">{{ $message }}</div>@enderror
                </div>
                <div class="field">
                    <label>Confirm password</label>
                    <input type="password" name="password_confirmation" required>
                </div>
            </div>
            <button class="btn" type="submit">Send verification code</button>
        </form>
        <div class="links"><span></span><a href="{{ route('login') }}">Already have an account?</a></div>
    @else
        <form method="POST" action="{{ route('register.verify') }}">
            @csrf
            <div class="field otp">
                <label>Verification code</label>
                <input name="otp_code" inputmode="numeric" maxlength="6" placeholder="000000" required autofocus>
                <small>The code is valid for 15 minutes.</small>
            </div>
            <button class="btn" type="submit">Verify &amp; create account</button>
        </form>
        <form method="POST" action="{{ route('register.resend') }}">
            @csrf
            <button class="btn btn-ghost" type="submit">Resend code</button>
        </form>
        <form method="POST" action="{{ route('register.reset') }}">
            @csrf
            <button class="btn btn-ghost" type="submit">Start over</button>
        </form>
    @endif
@endsection
