@extends('layouts.portal')
@section('title', 'My Bookings')

@push('head')
    @vite(['resources/js/customer-bookings.js'])
@endpush

@section('content')
<div id="bookings-page" class="owner-stack">
    <div class="page-header">
        <div class="page-title">
            <h1>My Bookings</h1>
            <p>Your upcoming and past appointments.</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('customer.services') }}" class="btn-primary"><i class="fas fa-plus"></i> Book a service</a>
        </div>
    </div>

    <section class="card">
        <div class="toolbar">
            <div class="search-box">
                <i class="fas fa-search" aria-hidden="true"></i>
                <input type="search" id="searchBookings" class="form-control" placeholder="Search by service or reference" aria-label="Search bookings">
            </div>
        </div>

        <div class="table-scroll">
            <table class="list-table" id="bookingsTable">
                <thead>
                    <tr><th>Service</th><th>Date</th><th>Status</th><th>Payment</th><th class="num">Amount</th><th class="col-actions"><span class="sr-only">Actions</span></th></tr>
                </thead>
                <tbody>
                @forelse ($bookings as $booking)
                    @php($needsProof = $booking->can_upload_proof && in_array($booking->paymentStatus, ['pending', 'rejected'], true))
                    <tr data-search="{{ strtolower($booking->service_name . ' ' . $booking->bookingRef) }}">
                        <td>
                            <span class="cell-main">{{ \Illuminate\Support\Str::limit($booking->service_name, 40) }}</span>
                            <span class="cell-sub">Ref {{ $booking->bookingRef }}</span>
                        </td>
                        <td class="nowrap">
                            <span class="cell-main" style="font-weight:500">{{ $booking->bookingDate->format('M d, Y') }}</span>
                            <span class="cell-sub">{{ \Carbon\Carbon::parse($booking->bookingTime)->format('g:i A') }}</span>
                        </td>
                        <td>@include('partials.status-badge', ['status' => $booking->bookingStatus])</td>
                        <td>
                            @include('partials.status-badge', ['status' => $booking->paymentStatus])
                            @if ($booking->paymentStatus === 'rejected')
                                <span class="cell-sub">Please send a new screenshot</span>
                            @endif
                        </td>
                        <td class="num">&#8369;{{ number_format((float) $booking->total_amount, 2) }}</td>
                        <td class="col-actions">
                            <div class="row-menu">
                                <button type="button" class="btn-menu" onclick="toggleMenu(this)" aria-label="Actions for {{ $booking->bookingRef }}"><i class="fas fa-ellipsis-v"></i></button>
                                <div class="menu-dropdown">
                                    <a href="{{ route('customer.bookings.show', $booking->bookingRef) }}"><i class="fas fa-eye"></i> View details</a>
                                    @if ($needsProof)
                                        <a href="{{ route('customer.bookings.payment', $booking->bookingRef) }}" class="menu-success"><i class="fas fa-upload"></i> Send payment proof</a>
                                    @endif
                                    @if ($booking->bookingStatus === 'completed' && Route::has('customer.feedback.create'))
                                        <a href="{{ route('customer.feedback.create', $booking->bookingRef) }}"><i class="fas fa-star"></i> Rate service</a>
                                    @endif
                                    @if ($booking->can_reschedule)
                                        <a href="{{ route('customer.bookings.reschedule', $booking->bookingRef) }}"><i class="fas fa-calendar-alt"></i> Reschedule</a>
                                    @endif
                                    @if ($booking->can_cancel)
                                        <form method="POST" action="{{ route('customer.bookings.cancel', $booking->bookingRef) }}" data-confirm="Cancel this booking?">
                                            @csrf
                                            <button type="submit" class="menu-danger"><i class="fas fa-times"></i> Cancel booking</button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="empty-state"><i class="fas fa-calendar-plus"></i>No bookings yet. <a href="{{ route('customer.services') }}" style="color:var(--primary);font-weight:600">Book a service</a></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div id="bookingsNoMatch" class="empty-state" style="display:none;">No bookings match your search.</div>
    </section>
</div>
@endsection
