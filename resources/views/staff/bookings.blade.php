@extends('layouts.portal')
@section('title', 'My Bookings')

@section('content')
    <div class="page-header">
        <div class="page-title">
            <h1>My Bookings</h1>
            <p>Online bookings and walk-ins assigned to you.</p>
        </div>
    </div>

    <section class="card">
        <div class="table-scroll">
            <table class="list-table">
                <thead><tr><th>Date</th><th>Customer</th><th>Service</th><th>Type</th><th>Status</th><th>Payment</th><th class="col-actions"><span class="sr-only">Actions</span></th></tr></thead>
                <tbody>
                    @forelse ($bookings as $booking)
                        <tr>
                            <td class="nowrap">
                                <span class="cell-main">{{ $booking->bookingDate->format('M d, Y') }}</span>
                                <span class="cell-sub">{{ \Illuminate\Support\Carbon::parse($booking->bookingTime)->format('g:i A') }}</span>
                            </td>
                            <td><span class="cell-main">{{ $booking->customer?->full_name }}</span><span class="cell-sub">{{ $booking->customer?->contactNumber }}</span></td>
                            <td>{{ $booking->serviceName }}</td>
                            <td>@include('partials.booking-type', ['booking' => $booking])</td>
                            <td>
                                @if ($booking->is_walkin && $booking->bookingStatus === 'approved')
                                    <span class="pill p-conf">In service</span>
                                @else
                                    @include('partials.status-badge', ['status' => $booking->bookingStatus])
                                @endif
                            </td>
                            <td>@include('partials.status-badge', ['status' => $booking->paymentStatus])</td>
                            <td class="col-actions">
                                <div class="row-menu">
                                    <button type="button" class="btn-menu" onclick="toggleMenu(this)" aria-label="Actions for {{ $booking->bookingRef }}"><i class="fas fa-ellipsis-v"></i></button>
                                    <div class="menu-dropdown">
                                        <a href="{{ route('staff.bookings.show', $booking) }}"><i class="fas fa-eye"></i> View details</a>
                                        @if ($booking->bookingStatus === 'pending')
                                            <form method="POST" action="{{ route('staff.bookings.approve', $booking) }}">
                                                @csrf
                                                <button type="submit" class="menu-success"><i class="fas fa-check"></i> Approve</button>
                                            </form>
                                        @elseif ($booking->bookingStatus === 'approved' && $booking->is_walkin)
                                            <form method="POST" action="{{ route('staff.walkin.complete', $booking) }}" onsubmit="return confirm('Mark this walk-in as completed?')">
                                                @csrf
                                                <button type="submit" class="menu-success"><i class="fas fa-check"></i> Mark completed</button>
                                            </form>
                                        @elseif ($booking->bookingStatus === 'approved')
                                            <a href="{{ route('staff.attendance') }}"><i class="fas fa-clipboard-check"></i> Record attendance</a>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="empty-state"><i class="fas fa-calendar"></i>No bookings are assigned to you.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $bookings->links() }}
    </section>
@endsection
