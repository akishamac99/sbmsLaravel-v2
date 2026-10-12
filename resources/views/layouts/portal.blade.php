@php
    $staffUser = auth('staff')->user();
    $customerUser = auth('customer')->user();
    $role = $staffUser ? $staffUser->role_slug : 'customer';
    $account = $staffUser ?? $customerUser;
    $roleLabel = [
        'owner' => 'Owner Portal',
        'manager' => 'Manager Portal',
        'staff' => 'Staff Portal',
        'customer' => 'Customer Portal',
    ][$role];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', $roleLabel) - {{ config('app.name') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    @vite(['resources/css/style.css', 'resources/js/app.js'])
        @stack('head')
</head>
<body>
<div class="dashboard-container">
    <div class="sidebar">
        <div class="brand">
            <div class="brand-name">Janet Angel's Beauty Salon</div>
            <div class="brand-role">{{ $roleLabel }}</div>
        </div>
        <nav class="nav-menu" aria-label="Main">
            @foreach (\App\Support\Nav::groups($role) as $group)
                @php
                    $groupActive = collect($group['items'])->contains(fn ($item) => request()->routeIs($item['route'], $item['route'] . '.*'));
                @endphp
                @if ($group['label'] === 'Settings')
                    <details class="nav-group nav-group-collapsible" @if ($groupActive) open @endif>
                        <summary class="nav-lbl"><span>Settings</span><i class="fas fa-chevron-down" aria-hidden="true"></i></summary>
                @else
                    <div class="nav-group">
                        @if ($group['label'] !== '')
                            <div class="nav-lbl">{{ $group['label'] }}</div>
                        @endif
                @endif
                @foreach ($group['items'] as $item)
                    @if (Route::has($item['route']))
                        <a href="{{ route($item['route']) }}" class="nav-item {{ request()->routeIs($item['route'], $item['route'] . '.*') ? 'active' : '' }}">
                            <i class="fas {{ $item['icon'] }}"></i> <span>{{ $item['label'] }}</span>
                            @if (str_ends_with($item['route'], '.notifications'))
                                @php $unread = \App\Models\UserNotification::forUser($role, (int) $account->getKey())->where('is_read', 0)->count(); @endphp
                                @if ($unread > 0)<span class="nav-badge">{{ $unread }}</span>@endif
                            @endif
                        </a>
                    @endif
                @endforeach
                @if ($group['label'] === 'Settings')
                    </details>
                @else
                    </div>
                @endif
            @endforeach
        </nav>
        <div class="sidebar-footer">
            <div class="user-info">
                <span>{{ $account->full_name }}</span>
                <small>{{ ucfirst($role) }}</small>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="logout-btn" style="border:0;cursor:pointer;width:100%">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </button>
            </form>
        </div>
    </div>

    <main class="main-content owner-stack">
        @yield('content')
    </main>
    @php
    $toasts = array_filter([
        session('status') ? ['ok', session('status')] : null,
        session('error') ? ['err', session('error')] : null,
    ]);
@endphp
@if ($toasts)
    <div class="toast-stack" role="status" aria-live="polite">
        @foreach ($toasts as [$type, $text])
            <div class="toast toast-{{ $type }}" data-toast>
                <i class="fas {{ $type === 'ok' ? 'fa-check-circle' : 'fa-exclamation-circle' }}"></i>
                <span>{{ $text }}</span>
                <button type="button" class="toast-close" aria-label="Close" data-toast-close>&times;</button>
            </div>
        @endforeach
    </div>
    <script>
        document.querySelectorAll('[data-toast]').forEach(function (toast) {
            var hide = function () {
                toast.classList.add('toast-out');
                setTimeout(function () { toast.remove(); }, 250);
            };
            toast.querySelector('[data-toast-close]').addEventListener('click', hide);
            setTimeout(hide, toast.classList.contains('toast-err') ? 8000 : 5000);
        });
    </script>
@endif
</div>
@include('partials.chat-bubble')
</body>
</html>
