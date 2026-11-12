@extends('layouts.auth')
@section('title', 'Forgot Password')
@section('hero')
    <h2>Forgot<br><em>your</em><br>password?</h2>
    <p>Enter your registered email and we'll send you a 6-digit code to set a new one.</p>
@endsection
@section('heading', 'Reset password')
@section('subheading', 'Step 1 of 3: we will email you a code')

@section('content')
    @if ($errors->any())
        <div class="alert alert-err" role="alert"><i class="fas fa-exclamation-circle" aria-hidden="true"></i> {{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <div class="field">
            <label for="fp-email">Email Address</label>
            <input id="fp-email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email">
        </div>
        <button class="btn" type="submit">Send code</button>
    </form>

    <div class="links"><a href="{{ route('login') }}">Back to sign in</a><span></span></div>
@endsection
