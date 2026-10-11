@extends('layouts.portal')
@section('title', 'Walk-In')

@push('head')
    @vite(['resources/js/portal-walkin.js'])
@endpush

@section('content')
    <div class="page-header">
        <div class="page-title">
            <h1>Walk-In</h1>
            <p>Check in a customer who is at the salon now. No approval step is needed.</p>
        </div>
    </div>

    @if ($canCreate)
        @php
            $servicesByCategory = $services->groupBy(fn ($service) => $service->category?->category_name ?? 'Other services');
        @endphp
        <form method="POST" action="{{ route($role . '.walkin.create') }}" class="walkin-layout" id="walkinForm"
              data-staff-url="{{ route($role . '.walkin.staff') }}"
              data-downpayment-percent="{{ $downpaymentPercent }}"
              data-old-staff="{{ old('staff_id') }}">
            @csrf

            <section class="card">
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert-inline" role="alert">{{ $errors->first() }}</div>
                    @endif

                    <div class="form-section">
                        <div class="form-section-title"><span class="step-no">1</span> Customer</div>
                        <div class="fields">
                            <label class="field field-full"><span class="field-label">Full name <span class="req" aria-hidden="true">*</span></span>
                                <input class="form-control" name="customer_name" value="{{ old('customer_name') }}" maxlength="100" required autocomplete="off" data-summary="customer">
                            </label>
                            <label class="field"><span class="field-label">Contact number <span class="req" aria-hidden="true">*</span></span>
                                <input class="form-control" name="contact_number" value="{{ old('contact_number') }}" maxlength="20" required inputmode="tel" placeholder="09XXXXXXXXX">
                            </label>
                            <label class="field">Email
                                <input class="form-control" type="email" name="customer_email" value="{{ old('customer_email') }}" maxlength="100" placeholder="Optional">
                                <span class="field-hint">For receipts and reminders. Returning customers are matched by contact number.</span>
                            </label>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="form-section-title"><span class="step-no">2</span> Service</div>
                        <label class="field"><span class="field-label">Service <span class="req" aria-hidden="true">*</span></span>
                            <select class="form-control" name="service_id" required>
                                <option value="">Select a service</option>
                                @foreach ($servicesByCategory as $category => $categoryServices)
                                    <optgroup label="{{ $category }}">
                                        @foreach ($categoryServices as $service)
                                            <option value="{{ $service->serviceID }}"
                                                    data-name="{{ $service->serviceName }}"
                                                    data-price="{{ (float) $service->servicePrice }}"
                                                    data-duration="{{ $service->duration_label }}"
                                                    @selected((string) old('service_id') === (string) $service->serviceID)>
                                                {{ $service->serviceName }} &middot; &#8369;{{ number_format((float) $service->servicePrice, 2) }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                        </label>
                    </div>

                    <div class="form-section">
                        <div class="form-section-title"><span class="step-no">3</span> Time and staff</div>
                        <div class="fields">
                            <label class="field"><span class="field-label">Time today <span class="req" aria-hidden="true">*</span></span>
                                <select class="form-control" name="booking_time" required @disabled(empty($slots))>
                                    @forelse ($slots as $index => $slot)
                                        <option value="{{ $slot }}" @selected(old('booking_time', $slots[0] ?? null) === $slot)>
                                            {{ \Illuminate\Support\Carbon::parse($slot)->format('g:i A') }}{{ $index === 0 ? ' (now)' : '' }}
                                        </option>
                                    @empty
                                        <option value="">No more slots today</option>
                                    @endforelse
                                </select>
                                <span class="field-hint">{{ today()->format('l, F j') }}</span>
                            </label>
                            <label class="field">Staff member
                                <select class="form-control" name="staff_id" disabled>
                                    <option value="">Choose a service first</option>
                                </select>
                                <span class="field-hint" data-staff-hint>Only staff who are free and qualified are listed.</span>
                            </label>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="form-section-title"><span class="step-no">4</span> Payment at the desk</div>
                        <div class="fields">
                            <div class="field">Method
                                <div class="choice-row">
                                    <label class="choice"><input type="radio" name="payment_method" value="cash" @checked(old('payment_method', 'cash') === 'cash')> Cash</label>
                                    <label class="choice"><input type="radio" name="payment_method" value="gcash" @checked(old('payment_method') === 'gcash')> GCash</label>
                                </div>
                            </div>
                            <div class="field">Amount paid now
                                <div class="choice-row">
                                    <label class="choice"><input type="radio" name="payment_type" value="full" @checked(old('payment_type', 'full') === 'full')> Full</label>
                                    <label class="choice"><input type="radio" name="payment_type" value="partial" @checked(old('payment_type') === 'partial')> <span>Downpayment <small>{{ rtrim(rtrim(number_format($downpaymentPercent, 2), '0'), '.') }}%, rest after the service</small></span></label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <aside class="card walkin-summary" aria-label="Walk-in summary">
                <div class="card-header"><span>Summary</span></div>
                <div class="card-body">
                    <dl class="kv">
                        <dt>Customer</dt><dd data-out="customer">&mdash;</dd>
                        <dt>Service</dt><dd data-out="service">&mdash;</dd>
                        <dt>Duration</dt><dd data-out="duration">&mdash;</dd>
                        <dt>Time</dt><dd data-out="time">&mdash;</dd>
                        <dt>Staff</dt><dd data-out="staff">First available</dd>
                        <dt>Service price</dt><dd data-out="price">&#8369;0.00</dd>
                        <dt>Balance after service</dt><dd data-out="balance">&#8369;0.00</dd>
                    </dl>
                    <div class="walkin-total"><span>Collect now</span><strong data-out="now">&#8369;0.00</strong></div>
                    <button type="submit" class="btn-primary w-full" style="margin-top:18px" @disabled(empty($slots))>
                        <i class="fas fa-user-check"></i> Check in walk-in
                    </button>
                </div>
            </aside>
        </form>
    @endif

    <section class="card">
        <div class="toolbar">
            <strong class="grow">Walk-ins</strong>
            <div class="seg" role="tablist" aria-label="Show">
                <a href="{{ route($role . '.walkin') }}" class="{{ $filter === 'today' ? 'active' : '' }}">Today</a>
                <a href="{{ route($role . '.walkin', ['show' => 'all']) }}" class="{{ $filter === 'all' ? 'active' : '' }}">All</a>
            </div>
        </div>
        <div class="table-scroll">
            <table class="list-table">
                <thead><tr><th>Time</th><th>Customer</th><th>Service</th><th>Staff</th><th class="num">Amount</th><th>Status</th><th class="col-actions"><span class="sr-only">Actions</span></th></tr></thead>
                <tbody>
                    @forelse ($bookings as $booking)
                        <tr>
                            <td class="nowrap">
                                <span class="cell-main">{{ \Illuminate\Support\Carbon::parse($booking->bookingTime)->format('g:i A') }}</span>
                                <span class="cell-sub">{{ $booking->bookingDate->isToday() ? 'Today' : $booking->bookingDate->format('M d, Y') }}</span>
                            </td>
                            <td>
                                <span class="cell-main">{{ $booking->customer?->full_name }}</span>
                                <span class="cell-sub">{{ $booking->customer?->contactNumber }}@if ($booking->customer?->contact_email) &middot; {{ $booking->customer->contact_email }}@endif</span>
                            </td>
                            <td>{{ $booking->serviceName }}</td>
                            <td>{{ $booking->staff?->full_name ?? 'Unassigned' }}</td>
                            <td class="num">&#8369;{{ number_format((float) $booking->total_amount, 2) }}</td>
                            <td>
                                @if ($booking->bookingStatus === 'approved')
                                    <span class="pill p-conf">In service</span>
                                @else
                                    @include('partials.status-badge', ['status' => $booking->bookingStatus])
                                @endif
                            </td>
                            <td class="col-actions">
                                @php
                                    $canComplete = $booking->bookingStatus === 'approved' && $canCreate
                                        && ($role !== 'staff' || (int) $booking->staffID === (int) auth('staff')->id());
                                    $canView = $role !== 'staff' || (int) $booking->staffID === (int) auth('staff')->id();
                                @endphp
                                @if ($canComplete || $canView)
                                    <div class="row-menu">
                                        <button type="button" class="btn-menu" onclick="toggleMenu(this)" aria-label="Actions for {{ $booking->bookingRef }}"><i class="fas fa-ellipsis-v"></i></button>
                                        <div class="menu-dropdown">
                                            @if ($canView)
                                                <a href="{{ route($role . '.bookings.show', [$booking, 'from' => 'walkin']) }}"><i class="fas fa-eye"></i> View details</a>
                                            @endif
                                            @if ($canComplete)
                                                <form method="POST" action="{{ route($role . '.walkin.complete', $booking) }}"
                                                      onsubmit="return confirm('{{ $booking->remaining_balance > 0 ? 'Collect the balance of PHP ' . number_format($booking->remaining_balance, 2) . ' and mark' : 'Mark' }} this walk-in as completed?')">
                                                    @csrf
                                                    <button type="submit" class="menu-success"><i class="fas fa-check"></i> Mark completed</button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="empty-state"><i class="fas fa-user-clock"></i>{{ $filter === 'today' ? 'No walk-ins yet today.' : 'No walk-ins recorded yet.' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $bookings->links() }}
    </section>
@endsection
