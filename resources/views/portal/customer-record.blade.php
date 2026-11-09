@extends('layouts.portal')
@section('title', 'Customer Record')

@section('content')
    <div class="back-row">
        <a href="{{ route($role . '.customers') }}"><i class="fas fa-arrow-left"></i> Back to customers</a>
    </div>

    <div class="page-header">
        <div class="page-title">
            <h1>{{ $customer->full_name }}</h1>
            <p>{{ $ownOnly ? 'Your bookings with this customer.' : 'Full booking history.' }}</p>
        </div>
    </div>

    <div class="content-grid">
        <div class="owner-stack">
            <section class="card">
                <div class="card-header"><span>Booking history</span></div>
                <div class="table-scroll">
                    <table class="list-table">
                        <thead><tr><th scope="col">Date</th><th scope="col">Service</th><th scope="col">Staff</th><th scope="col">Status</th><th scope="col" class="num">Total</th><th scope="col"><span class="sr-only">Open</span></th></tr></thead>
                        <tbody>
                            @forelse ($bookings as $booking)
                                <tr>
                                    <td class="nowrap">{{ $booking->bookingDate->format('M d, Y') }}, {{ \Illuminate\Support\Carbon::parse($booking->bookingTime)->format('g:i A') }}</td>
                                    <td>{{ $booking->service_name }}</td>
                                    <td>{{ $booking->staff_name ?? 'Not assigned' }}</td>
                                    <td>@include('partials.status-badge', ['status' => $booking->bookingStatus])</td>
                                    <td class="num">&#8369;{{ number_format((float) $booking->total_amount, 2) }}</td>
                                    <td><a class="btn-ghost btn-sm" href="{{ route($role . '.bookings.show', $booking) }}">Details</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="muted-text">No bookings yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-body">{{ $bookings->links() }}</div>
            </section>
        </div>

        <div class="owner-stack">
            <section class="card">
                <div class="card-header"><span>Customer</span></div>
                <div class="card-body">
                    <dl class="kv" style="grid-template-columns:110px 1fr">
                        <dt>Contact</dt><dd>{{ $customer->contactNumber }}</dd>
                        <dt>Email</dt><dd>{{ $customer->contact_email ?? 'Not provided' }}</dd>
                        <dt>Type</dt><dd>{{ $customer->is_walkin ? 'Walk-in (no login)' : 'Registered customer' }}</dd>
                        <dt>Customer since</dt><dd>{{ $customer->createdAt?->format('M d, Y') }}</dd>
                    </dl>
                </div>
            </section>
            <section class="card">
                <div class="card-header"><span>Summary</span></div>
                <div class="card-body">
                    <dl class="kv" style="grid-template-columns:140px 1fr">
                        @foreach (['pending' => 'Pending', 'approved' => 'Approved', 'completed' => 'Completed', 'cancelled' => 'Cancelled', 'declined' => 'Declined', 'no-show' => 'No-show'] as $key => $label)
                            <dt>{{ $label }}</dt><dd>{{ $counts[$key] ?? 0 }}</dd>
                        @endforeach
                    </dl>
                </div>
            </section>
        </div>
    </div>
@endsection
