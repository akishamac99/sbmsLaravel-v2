@extends('layouts.portal')
@section('title', 'My Profile')

@push('head')
    @vite(['resources/css/customer-profile.css', 'resources/js/customer-profile.js'])
@endpush

@section('content')
<div id="profile-page" class="owner-stack">
    <div class="page-header">
        <div class="page-title">
            <h1>My Profile</h1>
            <p>Keep your details current so we can reach you about your appointments.</p>
        </div>
    </div>

    <div class="profile-layout">
        @include('partials.profile-card', [
            'name' => trim($customer->firstName . ' ' . $customer->lastName),
            'roleLabel' => 'Customer',
            'email' => $customer->email,
            'contact' => $customer->contactNumber,
            'extra' => ['Member since' => $customer->createdAt?->format('F Y') ?? '—'],
        ])

        <div class="profile-forms">
            <form method="POST" action="{{ route('customer.profile.update') }}" class="card">
                @csrf @method('PUT')
                <div class="card-header"><span>Personal details</span></div>
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert-inline" role="alert">{{ $errors->first() }}</div>
                    @endif
                    <div class="fields fields-3">
                        <label class="field"><span class="field-label">First name <span class="req" aria-hidden="true">*</span></span>
                            <input class="form-control" name="firstName" value="{{ old('firstName', $customer->firstName) }}" required maxlength="50">
                        </label>
                        <label class="field">Middle name
                            <input class="form-control" name="middleName" value="{{ old('middleName', $customer->middleName) }}" maxlength="50" placeholder="Optional">
                        </label>
                        <label class="field"><span class="field-label">Last name <span class="req" aria-hidden="true">*</span></span>
                            <input class="form-control" name="lastName" value="{{ old('lastName', $customer->lastName) }}" required maxlength="50">
                        </label>
                    </div>
                    <div class="fields" style="margin-top:16px">
                        <label class="field">Email
                            <input class="form-control profile-readonly" value="{{ $customer->email }}" readonly tabindex="-1">
                            <span class="field-hint">Verified. Contact the salon to change it.</span>
                        </label>
                        <label class="field"><span class="field-label">Contact number <span class="req" aria-hidden="true">*</span></span>
                            <input class="form-control" name="contactNumber" value="{{ old('contactNumber', $customer->contactNumber) }}" required maxlength="20" inputmode="tel">
                        </label>
                        <label class="field field-full">Home address
                            <textarea class="form-control" name="home_address" rows="2" maxlength="500" placeholder="Only needed for home-service bookings">{{ old('home_address', $customer->home_address) }}</textarea>
                        </label>
                    </div>
                    <div class="form-actions"><button class="btn-primary" type="submit">Save changes</button></div>
                </div>
            </form>

            <form method="POST" action="{{ route('customer.profile.password') }}" class="card">
                @csrf @method('PUT')
                <div class="card-header"><span>Password</span></div>
                <div class="card-body">
                    @if ($errors->password->any())
                        <div class="alert-inline" role="alert">{{ $errors->password->first() }}</div>
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
</div>
@endsection
