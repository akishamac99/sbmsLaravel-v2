@extends('layouts.portal')
@section('title', 'Notifications')

@section('content')
<div class="owner-stack">
    <div class="page-header">
        <h1>Notifications</h1>
        <p>Stay updated on your booking status</p>
    </div>

    <div class="card">
        <div class="card-header customer-card-header">
            <span>All Notifications @if ($unreadCount > 0)<span class="nav-badge">{{ $unreadCount }} new</span>@endif</span>
            @if ($notifications->isNotEmpty() && $unreadCount > 0)
                <form method="POST" action="{{ route('customer.notifications.read-all') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline btn-sm">Mark All Read</button>
                </form>
            @endif
        </div>
        <div class="card-body">
            @forelse ($notifications as $notification)
                <div class="customer-list-item {{ $notification->is_read ? '' : 'is-unread' }}" style="margin-bottom:12px;">
                    <div class="customer-list-main">
                        <strong>{{ $notification->title }}</strong>
                        <p>{{ $notification->message }}</p>
                        <small>{{ $notification->created_at->format('M d, Y g:i A') }}</small>
                    </div>
                    @unless ($notification->is_read)
                        <form method="POST" action="{{ route('customer.notifications.read', $notification->notifID) }}">
                            @csrf
                            <button type="submit" class="btn btn-outline btn-sm">Mark Read</button>
                        </form>
                    @endunless
                </div>
            @empty
                <div class="customer-empty-state">No notifications yet.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection
