@extends('layouts.portal')
@section('title', 'My Profile')

@push('head')
    @vite(['resources/css/customer-profile.css', 'resources/js/customer-profile.js'])
@endpush

@section('content')
    @include('partials.settings-header', ['subtitle' => $staff->isOwner() ? 'Account, booking rules, manager access and activity history.' : 'Your account and working hours.'])

    <div class="profile-layout">
        @include('partials.profile-card', [
            'name' => $staff->full_name,
            'roleLabel' => $staff->role . ($staff->specialization && ! $staff->isOwner() ? ' · ' . $staff->specialization : ''),
            'email' => $staff->email,
            'contact' => $staff->contactNumber,
            'extra' => ['Username' => '@' . $staff->username, 'Member since' => $staff->created_at ? \Illuminate\Support\Carbon::parse($staff->created_at)->format('F Y') : '—'],
        ])

        <div class="profile-forms">
            <form method="POST" action="{{ route($staff->role_slug . '.profile.update') }}" class="card">
                @csrf @method('PUT')
                <div class="card-header"><span>Personal details</span></div>
                <div class="card-body">
                    @if ($errors->any() && ! $errors->has('current_password') && ! $errors->has('password'))
                        <div class="alert-inline" role="alert">{{ $errors->first() }}</div>
                    @endif
                    <div class="fields">
                        <label class="field"><span class="field-label">First name <span class="req" aria-hidden="true">*</span></span>
                            <input class="form-control" name="firstName" value="{{ old('firstName', $staff->firstName) }}" required maxlength="50">
                        </label>
                        <label class="field"><span class="field-label">Last name <span class="req" aria-hidden="true">*</span></span>
                            <input class="form-control" name="lastName" value="{{ old('lastName', $staff->lastName) }}" required maxlength="50">
                        </label>
                        <label class="field"><span class="field-label">Email <span class="req" aria-hidden="true">*</span></span>
                            <input class="form-control" type="email" name="email" value="{{ old('email', $staff->email) }}" required maxlength="100">
                            <span class="field-hint">You log in with this address.</span>
                        </label>
                        <label class="field">Contact number
                            <input class="form-control" name="contactNumber" value="{{ old('contactNumber', $staff->contactNumber) }}" maxlength="20" inputmode="tel">
                        </label>
                    </div>
                    <div class="form-actions"><button class="btn-primary" type="submit">Save changes</button></div>
                </div>
            </form>

            <form method="POST" action="{{ route($staff->role_slug . '.profile.password') }}" class="card">
                @csrf @method('PUT')
                <div class="card-header"><span>Password</span></div>
                <div class="card-body">
                    @if ($errors->has('current_password') || $errors->has('password'))
                        <div class="alert-inline" role="alert">{{ $errors->first('current_password') ?: $errors->first('password') }}</div>
                    @endif
                    <div class="fields">
                        <label class="field field-full">Current password
                            @include('partials.password-input', ['id' => 'pw_current', 'name' => 'current_password', 'autocomplete' => 'current-password'])
                        </label>
                        <label class="field">New password
                            @include('partials.password-input', ['id' => 'pw_new', 'name' => 'password', 'autocomplete' => 'new-password', 'minlength' => 8])
                            <span class="field-hint">At least 8 characters.</span>
                        </label>
                        <label class="field">Confirm new password
                            @include('partials.password-input', ['id' => 'pw_confirm', 'name' => 'password_confirmation', 'autocomplete' => 'new-password', 'minlength' => 8])
                        </label>
                    </div>
                    <div class="form-actions"><button class="btn-primary" type="submit">Update password</button></div>
                </div>
            </form>

            @include('partials.messenger-card')
        </div>
    </div>
@endsection
