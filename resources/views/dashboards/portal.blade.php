
@extends('layouts.portal')
@section('title', 'Dashboard')

@section('content')
    <div class="owner-hero-card">
        <div>
            <span class="badge"><i class="fas fa-chart-line"></i> {{ ucfirst($role) }} Overview</span>
            <h2>Welcome, {{ $user->full_name }}</h2>
            <p>{{ $role === 'staff' ? 'Your assigned appointments at a glance.' : 'Track daily bookings, approvals, payments, and operational work from one calm view.' }}</p>
        </div>
    </div>

    <div class="owner-metric-grid">
        @foreach ($metrics as [$icon, $label, $value, $color])
            <div class="owner-metric">
                <span class="owner-metric-icon"><i class="fas {{ $icon }}"></i></span>
                <strong class="owner-metric-value" @if ($color) style="color: {{ $color }};" @endif>{{ $value }}</strong>
                <span class="owner-metric-label">{{ $label }}</span>
            </div>
        @endforeach
    </div>

    <section class="card">
        <div class="card-header"><span>Recent Bookings</span></div>
        <div class="card-body">
            <div class="owner-table-wrap">
                <table class="table">
                    <thead>
                        <tr><th>Date</th><th>Customer</th><th>Service</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                    @forelse ($recent as $booking)
                        <tr>
                            <td>{{ $booking->bookingDate->format('M d, Y') }}</td>
                            <td>{{ $booking->customer?->full_name }}</td>
                            <td>{{ $booking->serviceItem?->serviceName }}</td>
                            <td>@include('partials.status-badge', ['status' => $booking->bookingStatus])</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" style="text-align:center;color:var(--brown-light)">No bookings yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endsection
