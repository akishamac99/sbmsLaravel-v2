@extends('layouts.portal')
@section('title', 'Upload Payment Proof')

@push('head')
    @vite(['resources/js/customer-checkout.js'])
@endpush

@section('content')
<div class="owner-stack">
    <div class="back-row">
        <a href="{{ route('customer.bookings.show', $booking->bookingRef) }}"><i class="fas fa-arrow-left"></i> Back to booking</a>
    </div>
    <div class="page-header">
        <div class="page-title">
            <h1>Send payment proof</h1>
            <p>{{ $booking->service_name }} &middot; {{ $booking->bookingDate->format('M d, Y') }} at {{ \Carbon\Carbon::parse($booking->bookingTime)->format('g:i A') }}</p>
        </div>
    </div>

    <form method="POST" action="{{ route('customer.bookings.payment.store', $booking->bookingRef) }}" enctype="multipart/form-data" class="card" style="max-width:720px">
        @csrf
        <div class="card-body">
            @if ($errors->any())
                <div class="alert-inline" role="alert">{{ $errors->first() }}</div>
            @endif
            <div class="form-section">
                <div class="form-section-title"><span class="step-no">1</span> Send &#8369;{{ number_format($booking->amount_due, 2) }} by GCash</div>
                <div class="pay-to">
                    <dl class="kv">
                        <dt>GCash number</dt><dd><strong>{{ $gcashNumber }}</strong></dd>
                        <dt>Account name</dt><dd>{{ $gcashName }}</dd>
                        <dt>Reference to include</dt><dd>{{ $booking->bookingRef }}</dd>
                    </dl>
                </div>
            </div>
            <div class="form-section">
                <div class="form-section-title"><span class="step-no">2</span> Attach the screenshot</div>
                @include('customer.partials.proof-upload')
            </div>
            <div class="form-actions">
                <a href="{{ route('customer.bookings') }}" class="btn-ghost">Later</a>
                <button type="submit" class="btn-primary" data-submit><i class="fas fa-upload"></i> Send proof</button>
            </div>
        </div>
    </form>
</div>
@endsection
