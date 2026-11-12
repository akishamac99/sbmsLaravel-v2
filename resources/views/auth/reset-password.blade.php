@extends('layouts.auth')
@section('title', 'Reset Password')
@section('hero')
    <h2>Choose<br><em>a new</em><br>password.</h2>
    <p>Use at least 8 characters that you haven't used elsewhere.</p>
@endsection
@section('heading', 'New password')
@section('subheading', 'Step 3 of 3: set a new password for ' . $email)

@section('content')
    @if ($errors->any())
        <div class="alert alert-err" role="alert"><i class="fas fa-exclamation-circle" aria-hidden="true"></i> {{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('password.update') }}">
        @csrf
        <div class="field">
            <label for="np">New password</label>
            <input id="np" type="password" name="password" required minlength="8" autocomplete="new-password" autofocus>
        </div>
        <div class="field">
            <label for="npc">Confirm new password</label>
            <input id="npc" type="password" name="password_confirmation" required minlength="8" autocomplete="new-password">
        </div>
        <button class="btn" type="submit">Change password</button>
    </form>
@endsection
