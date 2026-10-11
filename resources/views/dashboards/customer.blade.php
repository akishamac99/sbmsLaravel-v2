@extends('layouts.portal')
@section('title', 'Dashboard')

@section('content')
    <div class="owner-hero-card">
        <div>
            <span class="badge"><i class="fas fa-spa"></i> Customer</span>
            <h2>Welcome, {{ $user->full_name }}</h2>
            <p>Your appointments and notifications in one place.</p>
        </div>
    </div>

    <div class="owner-metric-grid">
        <div class="owner-metric">
            <span class="owner-metric-icon"><i class="fas fa-calendar-check"></i></span>
            <strong class="owner-metric-value">{{ $upcoming }}</strong>
            <span class="owner-metric-label">Upcoming</span>
        </div>
        <div class="owner-metric">
            <span class="owner-metric-icon"><i class="fas fa-check-circle"></i></span>
            <strong class="owner-metric-value" style="color:var(--success)">{{ $completed }}</strong>
            <span class="owner-metric-label">Completed</span>
        </div>
        <div class="owner-metric">
            <span class="owner-metric-icon"><i class="fas fa-bell"></i></span>
            <strong class="owner-metric-value" style="color:var(--rose-gold)">{{ $unread }}</strong>
            <span class="owner-metric-label">Unread notifications</span>
        </div>
    </div>

    <section class="card">
        <div class="card-header"><span>Recent Bookings</span></div>
        <div class="card-body">
            <div class="owner-table-wrap">
                <table class="table">
                    <thead>
                        <tr><th>Date</th><th>Service</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                    @forelse ($recent as $booking)
                        <tr>
                            <td>{{ $booking->bookingDate->format('M d, Y') }}</td>
                            <td>{{ $booking->serviceItem?->serviceName }}</td>
                            <td>@include('partials.status-badge', ['status' => $booking->bookingStatus])</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" style="text-align:center;color:var(--brown-light)">No bookings yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endsection
