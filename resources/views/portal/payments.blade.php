@extends('layouts.portal')
@section('title', 'Payments')

@section('content')
    <div class="page-header">
        <div class="page-title">
            <h1>Payments</h1>
            <p>Check payment screenshots from online bookings and see what was paid at the desk.</p>
        </div>
    </div>

    <section class="card">
        <div class="table-scroll">
            <table class="list-table">
                <thead><tr><th>Received</th><th>Customer</th><th>Service</th><th>Type</th><th>Method</th><th class="num">Amount</th><th>Status</th><th>Proof</th><th class="col-actions"><span class="sr-only">Actions</span></th></tr></thead>
                <tbody>
                    @forelse ($payments as $payment)
                        <tr>
                            <td class="nowrap">
                                <span class="cell-main">{{ $payment->payDate?->format('M d, Y') }}</span>
                                <span class="cell-sub">{{ $payment->payDate?->format('g:i A') }}</span>
                            </td>
                            <td>
                                <span class="cell-main">{{ $payment->booking?->customer?->full_name }}</span>
                                <span class="cell-sub">Ref {{ $payment->booking?->bookingRef }}</span>
                            </td>
                            <td>{{ $payment->booking?->serviceName }}</td>
                            <td>@if ($payment->booking) @include('partials.booking-type', ['booking' => $payment->booking]) @endif</td>
                            <td>{{ ['gcash' => 'GCash', 'bank_transfer' => 'Bank transfer', 'cash' => 'Cash'][$payment->payType] ?? ucfirst((string) $payment->payType) }}</td>
                            <td class="num">&#8369;{{ number_format((float) $payment->amount, 2) }}</td>
                            <td>@include('partials.status-badge', ['status' => $payment->payStatus])</td>
                            <td>
                                @if ($payment->payment_proof)
                                    <a href="{{ route('payment-proof', $payment) }}" target="_blank" rel="noopener" class="btn-ghost btn-sm"><i class="fas fa-image"></i> View</a>
                                @else
                                    <span class="muted-text">&mdash;</span>
                                @endif
                            </td>
                            <td class="col-actions">
                                <div class="row-menu">
                                    <button type="button" class="btn-menu" onclick="toggleMenu(this)" aria-label="Actions"><i class="fas fa-ellipsis-v"></i></button>
                                    <div class="menu-dropdown">
                                        @if ($payment->booking)
                                            <a href="{{ route($role . '.bookings.show', $payment->booking) }}"><i class="fas fa-eye"></i> View booking</a>
                                        @endif
                                        @if ($canReview && $payment->payStatus === 'proof-submitted')
                                            <form method="POST" action="{{ route($role . '.payments.confirm', $payment) }}">@csrf<button type="submit" class="menu-success"><i class="fas fa-check"></i> Confirm payment</button></form>
                                            <form method="POST" action="{{ route($role . '.payments.reject', $payment) }}" onsubmit="return confirm('Reject this payment proof? The customer will be asked to upload a new one.')">@csrf<button type="submit" class="menu-danger"><i class="fas fa-times"></i> Reject proof</button></form>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="empty-state"><i class="fas fa-receipt"></i>No payments yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $payments->links() }}
    </section>
@endsection
