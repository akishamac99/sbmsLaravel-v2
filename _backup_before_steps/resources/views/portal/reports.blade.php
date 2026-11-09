@extends('layouts.portal')
@section('title', 'Reports')

@push('head')
    @vite(['resources/js/portal-forms.js'])
@endpush

@section('content')
    @php
        $filterKeys = ['q', 'from', 'to', 'status', 'type', 'staff_id', 'service_id'];
        $hasFilters = count(array_filter(request()->only($filterKeys), fn ($v) => $v !== null && $v !== '')) > 0;
        $keep = request()->except(['page', 'from', 'to']);
        $presets = [
            'Today' => [today(), today()],
            'Last 7 days' => [today()->subDays(6), today()],
            'Last 30 days' => [today()->subDays(29), today()],
            'This month' => [today()->startOfMonth(), today()],
        ];
    @endphp

    <div class="page-header"><h1>Operational Reports</h1><p>Booking, revenue, service, and team summaries. Use the filters to focus on a period, a person or a service.</p></div>

    <section class="card">
        <form method="GET" class="toolbar" role="search">
            <div class="search-box">
                <i class="fas fa-search" aria-hidden="true"></i>
                <input class="form-control" type="search" name="q" value="{{ request('q') }}" placeholder="Search customer, phone, service or reference" aria-label="Search bookings" data-auto-search>
            </div>
            <input class="form-control" type="date" name="from" value="{{ request('from') }}" aria-label="From date" title="From (appointment date)" data-auto-submit>
            <input class="form-control" type="date" name="to" value="{{ request('to') }}" aria-label="To date" title="To (appointment date)" data-auto-submit>
            <select class="form-control" name="status" aria-label="Status" data-auto-submit>
                <option value="">All statuses</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst(str_replace('-', ' ', $status)) }}</option>
                @endforeach
            </select>
            <select class="form-control" name="type" aria-label="Booking type" data-auto-submit>
                <option value="">All types</option>
                <option value="online" @selected(request('type') === 'online')>Online bookings</option>
                <option value="walkin" @selected(request('type') === 'walkin')>Walk-ins</option>
            </select>
            <select class="form-control" name="staff_id" aria-label="Staff member" data-auto-submit>
                <option value="">All staff</option>
                @foreach ($staffOptions as $member)
                    <option value="{{ $member->staffID }}" @selected((string) request('staff_id') === (string) $member->staffID)>{{ $member->full_name }}</option>
                @endforeach
            </select>
            <select class="form-control" name="service_id" aria-label="Service" data-auto-submit>
                <option value="">All services</option>
                @foreach ($serviceOptions as $service)
                    <option value="{{ $service->serviceID }}" @selected((string) request('service_id') === (string) $service->serviceID)>{{ $service->serviceName }}</option>
                @endforeach
            </select>
            @if ($hasFilters)
                <a class="btn-ghost btn-sm" href="{{ route($role . '.reports') }}">Clear</a>
            @endif
        </form>
        <div class="toolbar">
            <span class="muted-text">Quick range:</span>
            @foreach ($presets as $label => [$start, $end])
                <a class="btn-ghost btn-sm" href="{{ route($role . '.reports', $keep + ['from' => $start->toDateString(), 'to' => $end->toDateString()]) }}">{{ $label }}</a>
            @endforeach
        </div>
    </section>

    <div class="owner-metric-grid">
        @foreach ([['Total Bookings', $totalBookings], ['Pending', $pendingBookings], ['Completed', $completedBookings], ['Completed Revenue', 'PHP ' . number_format($completedRevenue, 2)], ['Feedback', $feedbackCount]] as [$label, $value])
            <div class="owner-metric"><strong class="owner-metric-value">{{ $value }}</strong><span class="owner-metric-label">{{ $label }}</span></div>
        @endforeach
    </div>

    <section class="card"><div class="card-header"><span>Revenue by Service <span class="card-sub">Excludes cancelled, declined and no-show bookings.</span></span></div><div class="card-body owner-table-wrap">
        <table class="table"><thead><tr><th>Service</th><th>Category</th><th>Bookings</th><th>Revenue</th></tr></thead><tbody>
            @forelse ($byService as $row)<tr><td>{{ $row->serviceName }}</td><td>{{ $row->category ?? 'Uncategorized' }}</td><td>{{ $row->bookings_count }}</td><td>PHP {{ number_format((float) $row->total_revenue, 2) }}</td></tr>@empty<tr><td colspan="4" style="text-align:center;color:var(--brown-light)">No bookings match these filters.</td></tr>@endforelse
        </tbody></table>
    </div></section>

    <section class="card"><div class="card-header"><span>Staff Performance</span></div><div class="card-body owner-table-wrap">
        <table class="table"><thead><tr><th>Staff member</th><th>Completed bookings</th><th>Feedback</th><th>Average rating</th></tr></thead><tbody>
            @forelse ($staffPerformance as $row)<tr><td>{{ $row->staff_name }}</td><td>{{ $row->completed }}</td><td>{{ $row->feedback_count }}</td><td>{{ $row->avg_rating ? number_format((float) $row->avg_rating, 1) : '-' }}</td></tr>@empty<tr><td colspan="4" style="text-align:center;color:var(--brown-light)">No staff activity matches these filters.</td></tr>@endforelse
        </tbody></table>
    </div></section>

    <section class="card">
        <div class="card-header"><span>Booking records <span class="card-sub">{{ $records->total() }} {{ \Illuminate\Support\Str::plural('booking', $records->total()) }} match these filters.</span></span></div>
        <div class="table-scroll">
            <table class="list-table">
                <thead><tr><th>Date</th><th>Customer</th><th>Service</th><th>Staff</th><th>Status</th><th>Payment</th><th style="text-align:right">Amount</th></tr></thead>
                <tbody>
                    @forelse ($records as $booking)
                        <tr>
                            <td class="nowrap">
                                <span class="cell-main">{{ $booking->bookingDate->format('M d, Y') }}</span>
                                <span class="cell-sub">{{ \Illuminate\Support\Carbon::parse($booking->bookingTime)->format('g:i A') }}</span>
                            </td>
                            <td><span class="cell-main">{{ $booking->customer?->full_name }}</span><span class="cell-sub">{{ $booking->customer?->contactNumber }}</span></td>
                            <td>
                                <span class="cell-main" style="font-weight:500">{{ $booking->serviceName }}</span>
                                <span class="cell-sub">
                                    @if ($canOpen)
                                        <a href="{{ route($role . '.bookings.show', $booking) }}">Ref {{ $booking->bookingRef }}</a>
                                    @else
                                        Ref {{ $booking->bookingRef }}
                                    @endif
                                </span>
                            </td>
                            <td>{{ $booking->staff?->full_name ?? 'Unassigned' }}</td>
                            <td>@include('partials.status-badge', ['status' => $booking->bookingStatus])</td>
                            <td>@include('partials.status-badge', ['status' => $booking->paymentStatus])</td>
                            <td style="text-align:right" class="nowrap">PHP {{ number_format((float) $booking->total_amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="empty-state"><i class="fas fa-chart-bar"></i>No bookings match these filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $records->links() }}
    </section>
@endsection
