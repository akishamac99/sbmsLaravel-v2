@php
    $settingsRole = auth('staff')->user()?->role_slug ?? 'customer';
    $settingsLinks = \App\Support\Nav::settings($settingsRole);
@endphp
@if (count($settingsLinks) > 1)
    <nav class="sub-tabs" aria-label="Settings">
        @foreach ($settingsLinks as $link)
            <a href="{{ route($link['route']) }}" class="sub-tab {{ request()->routeIs($link['route']) ? 'active' : '' }}" @if (request()->routeIs($link['route'])) aria-current="page" @endif>
                <i class="fas {{ $link['icon'] }}" aria-hidden="true"></i> {{ $link['label'] }}
            </a>
        @endforeach
    </nav>
@endif
