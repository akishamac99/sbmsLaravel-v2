@extends('layouts.portal')
@section('title', 'Reports')

@section('content')
    <div class="page-header"><h1>Operational Reports</h1><p>Booking, revenue, service, and team summaries.</p></div>
    <div class="owner-metric-grid">
        @foreach ([['Total Bookings', $totalBookings], ['Pending', $pendingBookings], ['Completed', $completedBookings], ['Completed Revenue', 'PHP ' . number_format($completedRevenue, 2)], ['Feedback', $feedbackCount]] as [$label, $value])
            <div class="owner-metric"><strong class="owner-metric-value">{{ $value }}</strong><span class="owner-metric-label">{{ $label }}</span></div>
        @endforeach
    </div>
    <section class="card"><div class="card-header"><span>Revenue by Service</span></div><div class="card-body owner-table-wrap">
        <table class="table"><thead><tr><th>Service</th><th>Category</th><th>Bookings</th><th>Revenue</th></tr></thead><tbody>
            @forelse ($byService as $row)<tr><td>{{ $row->serviceName }}</td><td>{{ $row->category ?? 'Uncategorized' }}</td><td>{{ $row->bookings_count }}</td><td>PHP {{ number_format((float) $row->total_revenue, 2) }}</td></tr>@empty<tr><td colspan="4" style="text-align:center;color:var(--brown-light)">No completed activity yet.</td></tr>@endforelse
        </tbody></table>
    </div></section>
    <section class="card"><div class="card-header"><span>Staff Performance</span></div><div class="card-body owner-table-wrap">
        <table class="table"><thead><tr><th>Staff member</th><th>Completed bookings</th><th>Feedback</th><th>Average rating</th></tr></thead><tbody>
            @forelse ($staffPerformance as $row)<tr><td>{{ $row->staff_name }}</td><td>{{ $row->completed }}</td><td>{{ $row->feedback_count }}</td><td>{{ $row->avg_rating ? number_format((float) $row->avg_rating, 1) : '-' }}</td></tr>@empty<tr><td colspan="4" style="text-align:center;color:var(--brown-light)">No staff performance data yet.</td></tr>@endforelse
        </tbody></table>
    </div></section>
@endsection