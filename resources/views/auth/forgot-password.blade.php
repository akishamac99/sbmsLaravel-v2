@extends('layouts.auth')
@section('title', 'Forgot Password')
@section('hero')
    <h2>Forgot<br><em>your</em><br>password?</h2>
    <p>Enter your registered email and we'll send you a link to set a new one.</p>
@endsection
@section('heading', 'Reset password')
@section('subheading', 'We will email you a reset link')

@section('content')
    @if ($errors->any())
        <div class="alert alert-err"><i class="fas fa-exclamation-circle"></i> {{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <div class="field">
            <label>Email Address</label>
            <input type="email" name="email" value="{{ old('email') }}" required autofocus>
        </div>
        <button class="btn" type="submit">Send reset link</button>
    </form>

    <div class="links"><a href="{{ route('login') }}">Back to sign in</a><span></span></div>
@endsection
