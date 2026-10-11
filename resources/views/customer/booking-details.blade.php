@extends('layouts.portal')
@section('title', 'Booking Details')

@section('content')
@php
    $time = \Carbon\Carbon::parse($booking->bookingTime)->format('g:i A');
    $needsProof = $booking->can_upload_proof && in_array($booking->paymentStatus, ['pending', 'rejected', 'proof-submitted'], true);
    $proofs = $booking->payments()->whereNotNull('payment_proof')->latest('payDate')->get();
@endphp
<div class="owner-stack">
    <div class="back-row">
        <a href="{{ route('customer.bookings') }}"><i class="fas fa-arrow-left"></i> Back to my bookings</a>
    </div>

    <div class="page-header">
        <div class="page-title">
            <h1>{{ $booking->service_name }}</h1>
            <p>{{ $booking->bookingDate->format('l, F j, Y') }} at {{ $time }} &middot; Ref {{ $booking->bookingRef }}</p>
        </div>
        <div class="page-actions">
            @include('partials.status-badge', ['status' => $booking->bookingStatus])
        </div>
    </div>

    @if ($booking->paymentStatus === 'rejected')
        <div class="alert-inline" role="alert">The salon could not verify your last payment screenshot. Please send a clear one.</div>
    @endif

    <div class="content-grid">
        <section class="card">
            <div class="card-header"><span>Appointment</span></div>
            <div class="card-body">
                <dl class="kv">
                    <dt>Service</dt><dd>{{ $booking->service_name }}</dd>
                    <dt>Staff</dt><dd>{{ $booking->staff_name ?? 'The salon will assign someone' }}</dd>
                    <dt>Date and time</dt><dd>{{ $booking->bookingDate->format('F j, Y') }}, {{ $time }}</dd>
                    <dt>Where</dt><dd>{{ $booking->service_type === 'home_service' ? 'At your home' : 'At the salon' }}</dd>
                    @if ($booking->home_address)
                        <dt>Home address</dt><dd>{!! nl2br(e($booking->home_address)) !!}</dd>
                    @endif
                    @if ($booking->special_request)
                        <dt>Your notes</dt><dd>{!! nl2br(e($booking->special_request)) !!}</dd>
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
                <dl class="kv" style="grid-template-columns:1fr auto">
                    <dt>Total</dt><dd>&#8369;{{ number_format((float) $booking->total_amount, 2) }}</dd>
                    @if ((float) $booking->discount_amount > 0)
                        <dt>Discount</dt><dd class="text-success">&minus;&#8369;{{ number_format((float) $booking->discount_amount, 2) }}</dd>
                    @endif
                    <dt>{{ $booking->payment_type === 'partial' ? 'Downpayment' : 'Paid up front' }}</dt><dd>&#8369;{{ number_format($booking->amount_due, 2) }}</dd>
                    <dt>Balance at the salon</dt><dd>&#8369;{{ number_format($booking->remaining_balance, 2) }}</dd>
                </dl>

                @if ($proofs->isNotEmpty())
                    <div class="proof-list">
                        @foreach ($proofs as $payment)
                            <a href="{{ route('customer.payments.proof', $payment) }}" target="_blank" rel="noopener" class="proof-thumb" title="Sent {{ $payment->payDate?->format('M d, g:i A') }}">
                                <img src="{{ route('customer.payments.proof', $payment) }}" alt="Payment screenshot sent {{ $payment->payDate?->format('M d') }}">
                                @include('partials.status-badge', ['status' => $payment->payStatus])
                            </a>
                        @endforeach
                    </div>
                @endif

                @if ($needsProof)
                    @if (config('services.paymongo.secret'))
                        <form method="POST" action="{{ route('customer.bookings.pay-online', $booking->bookingRef) }}" style="margin-top:16px">
                            @csrf
                            <button type="submit" class="btn-primary w-full"><i class="fas fa-credit-card"></i> Pay online</button>
                        </form>
                        <a href="{{ route('customer.bookings.payment', $booking->bookingRef) }}" class="btn-outline w-full" style="margin-top:10px"><i class="fas fa-upload"></i> Send payment proof instead</a>
                    @else
                        <a href="{{ route('customer.bookings.payment', $booking->bookingRef) }}" class="btn-primary w-full" style="margin-top:16px"><i class="fas fa-upload"></i> Send payment proof</a>
                    @endif
                @endif
            </div>
        </section>
    </div>

    @if (in_array($booking->bookingStatus, ['pending', 'approved'], true) && ! $booking->is_walkin)
        <section class="card">
            <div class="card-header"><span>Need to change this booking?</span></div>
            <div class="card-body">
                @if ($booking->is_changeable_by_customer)
                    <p class="muted-text" style="font-size:.86rem">
                        You can reschedule or cancel until <strong>{{ $booking->change_deadline->format('l, M d \a\t g:i A') }}</strong>.
                        @if ($booking->reschedules_left > 0)
                            Reschedules left: {{ $booking->reschedules_left }}.
                        @else
                            You have used all your reschedules for this booking.
                        @endif
                        @if (in_array($booking->paymentStatus, ['confirmed', 'paid', 'proof-submitted'], true))
                            If you cancel, the salon will contact you about the payment you sent.
                        @endif
                    </p>
                    <div class="form-actions start" style="margin-top:14px">
                        @if ($booking->can_reschedule)
                            <a href="{{ route('customer.bookings.reschedule', $booking->bookingRef) }}" class="btn-outline btn-sm"><i class="fas fa-calendar-alt"></i> Reschedule</a>
                        @endif
                        <form method="POST" action="{{ route('customer.bookings.cancel', $booking->bookingRef) }}" onsubmit="return confirm('Cancel this booking?')">
                            @csrf
                            <button type="submit" class="btn-ghost btn-sm"><i class="fas fa-times"></i> Cancel booking</button>
                        </form>
                    </div>
                @else
                    <p class="muted-text" style="font-size:.86rem">
                        Online changes closed on {{ $booking->change_deadline->format('M d \a\t g:i A') }}
                        ({{ \App\Models\SystemPolicy::cancellationWindowHours() }} hours before your appointment). Please call the salon if you need to change it.
                    </p>
                @endif
            </div>
        </section>
    @endif
</div>
@endsection
