@extends('layouts.portal')
@section('title', 'Booking Details')

@section('content')
@php
    $time = \Illuminate\Support\Carbon::parse($booking->bookingTime)->format('g:i A');
    $backRoute = request('from') === 'walkin' ? $role . '.walkin' : $role . '.bookings';
@endphp

<div class="back-row">
    <a href="{{ route($backRoute) }}"><i class="fas fa-arrow-left"></i> Back to {{ $backRoute === $role . '.walkin' ? 'walk-ins' : 'bookings' }}</a>
</div>

<div class="page-header">
    <div class="page-title">
        <h1>{{ $booking->service_name }}</h1>
        <p>
            {{ $booking->bookingDate->format('l, F j, Y') }} at {{ $time }}
            &middot; Ref {{ $booking->bookingRef }}
        </p>
    </div>
    <div class="page-actions">
        @include('partials.booking-type', ['booking' => $booking])
        @include('partials.status-badge', ['status' => $booking->bookingStatus])
    </div>
</div>

<div class="content-grid">
    <div class="owner-stack">
        <section class="card">
            <div class="card-header"><span>Appointment</span></div>
            <div class="card-body">
                <dl class="kv">
                    <dt>Service</dt><dd>{{ $booking->service_name }}</dd>
                    <dt>Staff</dt><dd>{{ $booking->staff_name ?? 'Not assigned yet' }}</dd>
                    <dt>Date and time</dt><dd>{{ $booking->bookingDate->format('F j, Y') }}, {{ $time }}</dd>
                    <dt>Booked through</dt><dd>{{ $booking->is_walkin ? 'Walk-in at the front desk' : 'Customer portal' }}</dd>
                    @if ((int) $booking->reschedule_count > 0)
                        <dt>Rescheduled</dt><dd>{{ $booking->reschedule_count }} {{ \Illuminate\Support\Str::plural('time', (int) $booking->reschedule_count) }} by the customer</dd>
                    @endif
                    @unless ($booking->is_walkin)
                        <dt>Attendance</dt><dd>@include('partials.status-badge', ['status' => $booking->attendance_status ?: 'pending'])</dd>
                    @endunless
                    @if ($booking->home_address)
                        <dt>Home service address</dt><dd>{!! nl2br(e($booking->home_address)) !!}</dd>
                    @endif
                    @if ($booking->special_request)
                        <dt>Special request</dt><dd>{!! nl2br(e($booking->special_request)) !!}</dd>
                    @endif
                    @if ($booking->cancellation_reason)
                        <dt>Cancellation</dt><dd>{{ $booking->cancellation_reason }}</dd>
                    @endif
                </dl>
            </div>
        </section>

        <section class="card">
            <div class="card-header"><span>Payment</span> @include('partials.status-badge', ['status' => $booking->paymentStatus])</div>
            <div class="card-body">
                <dl class="kv">
                    <dt>Total</dt><dd>&#8369;{{ number_format((float) $booking->total_amount, 2) }}</dd>
                    <dt>{{ $booking->payment_type === 'partial' ? 'Downpayment' : 'Paid up front' }}</dt><dd>&#8369;{{ number_format($booking->amount_due, 2) }}</dd>
                    @if ((float) $booking->discount_amount > 0)
                        <dt>Discount</dt><dd>&#8369;{{ number_format((float) $booking->discount_amount, 2) }}{{ $booking->discount_reason ? ' (' . $booking->discount_reason . ')' : '' }}</dd>
                    @endif
                    <dt>Balance on the day</dt><dd>&#8369;{{ number_format($booking->remaining_balance, 2) }}</dd>
                </dl>
            </div>
            @if ($booking->payments->isNotEmpty())
                <div class="table-scroll" style="border-top:1px solid rgba(139,58,82,0.08)">
                    <table class="list-table">
                        <thead><tr><th>Date</th><th>Method</th><th class="num">Amount</th><th>Status</th><th>Proof</th></tr></thead>
                        <tbody>
                            @foreach ($booking->payments->sortByDesc('payDate') as $payment)
                                <tr>
                                    <td class="nowrap">{{ $payment->payDate?->format('M d, Y g:i A') }}</td>
                                    <td>{{ $payment->payType === 'bank_transfer' ? 'Bank transfer' : ($payment->payType === 'gcash' ? 'GCash' : ucfirst((string) $payment->payType)) }}</td>
                                    <td class="num">&#8369;{{ number_format((float) $payment->amount, 2) }}</td>
                                    <td>@include('partials.status-badge', ['status' => $payment->payStatus])</td>
                                    <td>
                                        @if ($payment->payment_proof)
                                            <a href="{{ route('payment-proof', $payment) }}" target="_blank" rel="noopener" class="btn-ghost btn-sm"><i class="fas fa-image"></i> View</a>
                                        @else
                                            <span class="muted-text">&mdash;</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        @if ($booking->feedback)
            <section class="card">
                <div class="card-header"><span>Customer feedback</span></div>
                <div class="card-body">
                    <dl class="kv">
                        <dt>Service rating</dt><dd>{{ $booking->feedback->service_rating ?? $booking->feedback->rating }}/5</dd>
                        <dt>Staff rating</dt><dd>{{ $booking->feedback->staff_rating ?? $booking->feedback->rating }}/5</dd>
                        @if ($booking->feedback->comment)
                            <dt>Comment</dt><dd>{!! nl2br(e($booking->feedback->comment)) !!}</dd>
                        @endif
                    </dl>
                </div>
            </section>
        @endif
    </div>

    <div class="owner-stack">
        <section class="card">
            <div class="card-header"><span>Customer</span></div>
            <div class="card-body">
                @if ($booking->customer)
                    <dl class="kv" style="grid-template-columns:110px 1fr">
                        <dt>Name</dt><dd>{{ $booking->customer->full_name }}</dd>
                        <dt>Contact</dt><dd>{{ $booking->customer->contactNumber }}</dd>
                        <dt>Email</dt><dd>{{ $booking->customer->contact_email ?? 'Not provided' }}</dd>
                        <dt>Account</dt><dd>{{ $booking->customer->is_walkin ? 'Walk-in (no login)' : 'Registered customer' }}</dd>
                    </dl>
                @else
                    <p class="muted-text">Customer record not found.</p>
                @endif
            </div>
        </section>

        @if ($canCancel)
            <section class="card">
                <div class="card-header"><span>Cancel booking</span></div>
                <div class="card-body">
                    <form method="POST" action="{{ route($role . '.bookings.cancel', $booking) }}" style="display:grid;gap:12px"
                          onsubmit="return confirm('Cancel booking {{ $booking->bookingRef }}? The customer will be notified.');">
                        @csrf
                        <label class="field">Reason <span class="field-hint">The customer will see this.</span>
                            <textarea class="form-control" name="reason" rows="3" maxlength="500" required>{{ old('reason') }}</textarea>
                        </label>
                        @error('reason')<div class="field-error" role="alert">{{ $message }}</div>@enderror
                        @if (in_array($booking->paymentStatus, ['confirmed', 'paid'], true))
                            <div class="note-inline" role="note">Payment was already confirmed. Cancelling does not refund it automatically.</div>
                        @endif
                        <div><button type="submit" class="btn-danger btn-sm">Cancel this booking</button></div>
                    </form>
                </div>
            </section>
        @endif
    </div>
</div>
@endsection
