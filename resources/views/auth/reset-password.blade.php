@extends('layouts.auth')
@section('title', 'Reset Password')
@section('hero')
    <h2>Choose<br><em>a new</em><br>password.</h2>
    <p>Pick something at least 6 characters long that you haven't used elsewhere.</p>
@endsection
@section('heading', 'New password')
@section('subheading', 'Set a new password for your account')

@section('content')
    @if ($errors->any())
        <div class="alert alert-err"><i class="fas fa-exclamation-circle"></i> {{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('password.update', ['broker' => $broker]) }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <div class="field">
            <label>Email</label>
            <input type="email" name="email" value="{{ old('email', $email) }}" required>
        </div>
        <div class="field">
            <label>New password</label>
            <input type="password" name="password" required>
        </div>
        <div class="field">
            <label>Confirm new password</label>
            <input type="password" name="password_confirmation" required>
        </div>
        <button class="btn" type="submit">Reset password</button>
    </form>
@endsection
