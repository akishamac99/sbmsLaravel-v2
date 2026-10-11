@extends('layouts.auth')
@section('title', 'Login')
@section('hero')
    <h2>Welcome<br><em>back</em><br>beautiful.</h2>
    <p>Sign in to manage appointments, bookings, services, and your salon experience.</p>
@endsection
@section('heading', 'Sign in')
@section('subheading', "Access your Janet's Beauty Salon account")

@section('content')
    @if ($errors->any())
        <div class="alert alert-err"><i class="fas fa-exclamation-circle"></i> {{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('login.store') }}">
        @csrf
        <div class="field">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="you@example.com" required autofocus>
            <small>All users log in with their email address</small>
        </div>
        <div class="field">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="Enter password" required>
        </div>
        <button class="btn" type="submit">Sign in</button>
    </form>

    <div class="links">
        <a href="{{ route('password.request') }}">Forgot password?</a>
        <a href="{{ route('register') }}">Create an account</a>
    </div>
@endsection
