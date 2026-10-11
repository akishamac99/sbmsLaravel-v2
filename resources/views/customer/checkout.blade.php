@extends('layouts.portal')
@section('title', 'Checkout')

@push('head')
    @vite(['resources/js/customer-checkout.js'])
@endpush

@section('content')
@php
    $bankReady = filled(config('salon.bank_account_number'));
    $online = filled(config('services.paymongo.secret'));
    $method = old('payment_method', $online ? 'paymongo' : 'gcash');
    if ($method === 'bank_transfer' && ! $bankReady) {
        $method = 'gcash';
    }
@endphp
<div id="checkout-page" class="owner-stack">
    <div class="back-row">
        <a href="{{ route('customer.services') }}"><i class="fas fa-arrow-left"></i> Back to services</a>
    </div>

    <div class="page-header">
        <div class="page-title">
            <h1>Checkout</h1>
            <p>Pay the amount shown, attach the screenshot, then place your booking.</p>
        </div>
    </div>

    <div class="checkout-layout">
        <form method="POST" action="{{ route('customer.checkout.place') }}" enctype="multipart/form-data" id="checkoutForm" class="owner-stack">
            @csrf

            @if ($errors->any())
                <div class="alert-inline" role="alert">{{ $errors->first() }}</div>
            @endif

            <section class="card">
                <div class="card-header"><span>Your appointments</span></div>
                <div class="table-scroll">
                    <table class="list-table">
                        <thead><tr><th>Service</th><th>When</th><th>Staff</th><th class="num">Price</th></tr></thead>
                        <tbody>
                            @foreach ($quote['lines'] as $line)
                                @php($item = $line['item'])
                                <tr>
                                    <td>
                                        <span class="cell-main">{{ $item->service->serviceName }}</span>
                                        <span class="cell-sub">{{ $line['mode'] === 'home_service' ? 'Home service' : 'In salon' }}</span>
                                    </td>
                                    <td class="nowrap">
                                        <span class="cell-main" style="font-weight:500">{{ \Carbon\Carbon::parse($item->bookingDate)->format('D, M d') }}</span>
                                        <span class="cell-sub">{{ \Carbon\Carbon::parse($item->bookingTime)->format('g:i A') }}</span>
                                    </td>
                                    <td>{{ $item->staff?->full_name ?? 'To be assigned' }}</td>
                                    <td class="num">&#8369;{{ number_format($line['price'], 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="card">
                <div class="card-header"><span>Payment</span></div>
                <div class="card-body">
                    <div class="form-section">
                        <div class="form-section-title"><span class="step-no">1</span> How much will you pay now?</div>
                        <div class="choice-row">
                            <label class="choice">
                                <input type="radio" name="payment_type" value="partial" @checked(old('payment_type', 'partial') === 'partial')>
                                <span>Downpayment &middot; &#8369;{{ number_format($quote['downpayment'], 2) }}<small>{{ number_format($quote['percent']) }}% now, the rest at the salon</small></span>
                            </label>
                            <label class="choice">
                                <input type="radio" name="payment_type" value="full" @checked(old('payment_type') === 'full')>
                                <span>Full payment &middot; &#8369;{{ number_format($quote['total'], 2) }}<small>Nothing more to pay on the day</small></span>
                            </label>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="form-section-title"><span class="step-no">2</span> {{ $online ? 'Choose how to pay' : 'Send the payment' }}</div>
                        <div class="choice-row" style="margin-bottom:14px">
                            @if ($online)
                                <label class="choice"><input type="radio" name="payment_method" value="paymongo" data-pay-method @checked($method === 'paymongo')> <span>Pay online<small>GCash, Maya or card. Instant, no screenshot needed</small></span></label>
                            @endif
                            <label class="choice"><input type="radio" name="payment_method" value="gcash" data-pay-method @checked($method === 'gcash')> <span>GCash / e-wallet<small>Send it yourself, then attach a screenshot</small></span></label>
                            @if ($bankReady)
                                <label class="choice"><input type="radio" name="payment_method" value="bank_transfer" data-pay-method @checked($method === 'bank_transfer')> <span>Bank transfer<small>Send it yourself, then attach a screenshot</small></span></label>
                            @endif
                        </div>
                        @if ($online)
                            <div class="pay-to" data-pay-details="paymongo">
                                <p>After you place the booking, you will go to PayMongo's secure page to pay. You come back here when it is done.</p>
                            </div>
                        @endif
                        <div class="pay-to" data-pay-details="gcash">
                            <dl class="kv">
                                <dt>GCash number</dt><dd><strong>{{ config('salon.gcash_number') }}</strong></dd>
                                <dt>Account name</dt><dd>{{ config('salon.gcash_name') }}</dd>
                            </dl>
                        </div>
                        @if ($bankReady)
                            <div class="pay-to" data-pay-details="bank_transfer" hidden>
                                <dl class="kv">
                                    <dt>Bank</dt><dd>{{ config('salon.bank_name') }}</dd>
                                    <dt>Account number</dt><dd><strong>{{ config('salon.bank_account_number') }}</strong></dd>
                                    <dt>Account name</dt><dd>{{ config('salon.bank_account_name') }}</dd>
                                </dl>
                            </div>
                        @endif
                    </div>

                    <div class="form-section" data-proof-section @if ($method === 'paymongo') hidden @endif>
                        <div class="form-section-title"><span class="step-no">3</span> Attach your payment screenshot</div>
                        @include('customer.partials.proof-upload')
                    </div>

                    <div class="form-section">
                        <div class="form-section-title"><span class="step-no">4</span> Anything else?</div>
                        <div class="fields">
                            @if ($quote['has_home'])
                                <label class="field field-full"><span class="field-label">Home address <span class="req" aria-hidden="true">*</span></span>
                                    <textarea name="home_address" class="form-control" rows="2" placeholder="Where should we go for the home service?" required>{{ old('home_address', $customer->home_address) }}</textarea>
                                </label>
                            @endif
                            <label class="field field-full">Notes for the salon
                                <textarea name="special_request" class="form-control" rows="2" maxlength="500" placeholder="Optional">{{ old('special_request') }}</textarea>
                            </label>
                        </div>
                    </div>
                </div>
            </section>
        </form>

        <aside class="card checkout-summary" aria-label="Order summary">
            <div class="card-header"><span>Summary</span></div>
            <div class="card-body">
                <form method="POST" action="{{ route('customer.checkout.promo') }}" id="promoForm" class="promo-block">
                    @csrf
                    @if ($quote['promo_valid'] && $promoCode !== '')
                        <div class="promo-chip">
                            <span><i class="fas fa-tag"></i> <strong>{{ strtoupper($promoCode) }}</strong> applied</span>
                            <button type="submit" name="remove_promo" value="1" class="btn-ghost btn-sm">Remove</button>
                        </div>
                        @if ($quote['discount_reason'])
                            <p class="field-hint" style="margin-top:6px">{{ $quote['discount_reason'] }}</p>
                        @endif
                    @else
                        <label class="field">Promo code
                            <span style="display:flex;gap:8px">
                                <input type="text" name="promo_code" class="form-control" value="{{ old('promo_code') }}" placeholder="Enter code" style="text-transform:uppercase">
                                <button type="submit" class="btn-outline btn-sm">Apply</button>
                            </span>
                        </label>
                    @endif
                </form>

                <dl class="kv summary-kv">
                    <dt>Services</dt><dd>&#8369;{{ number_format($quote['subtotal'] - ($quote['has_home'] ? $quote['home_fee'] : 0), 2) }}</dd>
                    @if ($quote['has_home'])
                        <dt>Home service fee</dt><dd>&#8369;{{ number_format($quote['home_fee'], 2) }}</dd>
                    @endif
                    @if ($quote['discount'] > 0)
                        <dt>Discount</dt><dd class="text-success">&minus;&#8369;{{ number_format($quote['discount'], 2) }}</dd>
                    @endif
                    <dt>Total</dt><dd><strong>&#8369;{{ number_format($quote['total'], 2) }}</strong></dd>
                </dl>
                <div class="walkin-total">
                    <span>Pay now</span>
                    <strong data-pay-now data-full="{{ number_format($quote['total'], 2) }}" data-partial="{{ number_format($quote['downpayment'], 2) }}">&#8369;{{ number_format(old('payment_type') === 'full' ? $quote['total'] : $quote['downpayment'], 2) }}</strong>
                </div>
                <button type="submit" form="checkoutForm" class="btn-primary w-full" style="margin-top:18px" data-submit>
                    <i class="fas fa-calendar-check"></i> Place booking
                </button>
                <p class="field-hint" style="margin-top:10px;text-align:center">The salon confirms your booking after checking the payment.</p>
            </div>
        </aside>
    </div>
</div>

<script>
    // Applying a promo reloads the page: carry over what the customer already typed
    // (the screenshot has to be picked after applying, so the promo box sits above the button).
    document.getElementById('promoForm').addEventListener('submit', function () {
        var promo = this;
        ['payment_type', 'payment_method', 'home_address', 'special_request'].forEach(function (name) {
            var source = document.querySelector('#checkoutForm [name="' + name + '"]:checked') || document.querySelector('#checkoutForm textarea[name="' + name + '"]');
            if (!source) return;
            var copy = document.createElement('input');
            copy.type = 'hidden';
            copy.name = name;
            copy.value = source.value;
            promo.appendChild(copy);
        });
    });
    document.querySelectorAll('#checkoutForm [name=payment_type]').forEach(function (radio) {
        radio.addEventListener('change', function () {
            var out = document.querySelector('[data-pay-now]');
            out.textContent = '₱' + (radio.value === 'full' ? out.dataset.full : out.dataset.partial);
        });
    });
</script>
@endsection
