@php
    $settingsRole = auth('staff')->user()?->role_slug ?? 'customer';
    $settingsCount = count(\App\Support\Nav::settings($settingsRole));
@endphp
<div class="page-header">
    <div class="page-title">
        <h1>{{ $settingsCount > 1 ? 'Settings' : 'My Profile' }}</h1>
        <p>{{ $settingsCount > 1 ? ($subtitle ?? 'Your account and salon settings.') : 'Your account details and password.' }}</p>
    </div>
</div>
@include('partials.settings-tabs')
