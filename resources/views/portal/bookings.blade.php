@extends('layouts.portal')
@section('title', 'Manage Bookings')

@push('head')
    @vite(['resources/js/portal-forms.js'])
@endpush

@section('content')
    @php
        $currentStatus = request('status', '');
        $baseQuery = array_filter(request()->only(['q', 'type']));
        $allCount = $counts->sum();
    @endphp

    <div class="page-header">
        <div class="page-title">
            <h1>Manage Bookings</h1>
            <p>Approve online requests and follow every appointment, online or walk-in.</p>
        </div>
    </div>

    <section class="card">
        <div class="toolbar">
            <nav class="seg" aria-label="Booking status">
                <a href="{{ route($role . '.bookings', $baseQuery) }}" class="{{ $currentStatus === '' ? 'active' : '' }}">All <span class="seg-count">{{ $allCount }}</span></a>
                @foreach ($statuses as $status)
                    <a href="{{ route($role . '.bookings', $baseQuery + ['status' => $status]) }}" class="{{ $currentStatus === $status ? 'active' : '' }}">
                        {{ ucfirst(str_replace('-', ' ', $status)) }} <span class="seg-count">{{ $counts[$status] ?? 0 }}</span>
                    </a>
                @endforeach
            </nav>
        </div>
        <form method="GET" class="toolbar" role="search">
            @if ($currentStatus !== '')
                <input type="hidden" name="status" value="{{ $currentStatus }}">
            @endif
            <div class="search-box">
                <i class="fas fa-search" aria-hidden="true"></i>
                <input class="form-control" type="search" name="q" value="{{ request('q') }}" placeholder="Search customer, phone, service or reference" aria-label="Search bookings" data-auto-search>
            </div>
            <select class="form-control" name="type" aria-label="Booking type" data-auto-submit>
                <option value="">All types</option>
                <option value="online" @selected(request('type') === 'online')>Online bookings</option>
                <option value="walkin" @selected(request('type') === 'walkin')>Walk-ins</option>
            </select>
            @if (request()->filled('q') || request()->filled('type'))
                <a class="btn-ghost btn-sm" href="{{ route($role . '.bookings', array_filter(['status' => $currentStatus])) }}">Clear</a>
            @endif
        </form>

        <div class="table-scroll">
            <table class="list-table">
                <thead><tr><th>Date</th><th>Customer</th><th>Service</th><th>Type</th><th>Staff</th><th>Status</th><th>Payment</th><th class="col-actions"><span class="sr-only">Actions</span></th></tr></thead>
                <tbody>
                    @forelse ($bookings as $booking)
                        <tr>
                            <td class="nowrap">
                                <span class="cell-main">{{ $booking->bookingDate->format('M d, Y') }}</span>
                                <span class="cell-sub">{{ \Illuminate\Support\Carbon::parse($booking->bookingTime)->format('g:i A') }}</span>
                            </td>
                            <td>
                                <span class="cell-main">{{ $booking->customer?->full_name }}</span>
                                <span class="cell-sub">{{ $booking->customer?->contactNumber }}</span>
                            </td>
                            <td>
                                <span class="cell-main" style="font-weight:500">{{ $booking->serviceName }}</span>
                                <span class="cell-sub">Ref {{ $booking->bookingRef }}</span>
                            </td>
                            <td>@include('partials.booking-type', ['booking' => $booking])</td>
                            <td>{{ $booking->staff?->full_name ?? 'Unassigned' }}</td>
                            <td>@include('partials.status-badge', ['status' => $booking->bookingStatus])</td>
                            <td>@include('partials.status-badge', ['status' => $booking->paymentStatus])</td>
                            <td class="col-actions">
                                <div class="row-menu">
                                    <button type="button" class="btn-menu" onclick="toggleMenu(this)" aria-label="Actions for {{ $booking->bookingRef }}"><i class="fas fa-ellipsis-v"></i></button>
                                    <div class="menu-dropdown">
                                        <a href="{{ route($role . '.bookings.show', $booking) }}"><i class="fas fa-eye"></i> View details</a>
                                        @if ($canEdit && $booking->bookingStatus === 'pending')
                                            <button type="button" class="menu-success"
                                                    data-approve="{{ route($role . '.bookings.approve', $booking) }}"
                                                    data-ref="{{ $booking->bookingRef }}"
                                                    data-summary="{{ $booking->customer?->full_name }} &middot; {{ $booking->serviceName }} &middot; {{ $booking->bookingDate->format('M d') }}, {{ \Illuminate\Support\Carbon::parse($booking->bookingTime)->format('g:i A') }}"
                                                    data-current="{{ $booking->staffID }}"
                                                    data-staff="{{ json_encode($availableStaff[$booking->bookingID] ?? []) }}">
                                                <i class="fas fa-check"></i> Approve&hellip;
                                            </button>
                                            <form method="POST" action="{{ route($role . '.bookings.decline', $booking) }}" onsubmit="return confirm('Decline booking {{ $booking->bookingRef }}? The customer will be notified.')">
                                                @csrf
                                                <button type="submit" class="menu-danger"><i class="fas fa-times"></i> Decline</button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="empty-state"><i class="fas fa-calendar-times"></i>No bookings match these filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $bookings->links() }}
    </section>

    @if ($canEdit)
        <div class="modal" id="approveModal" role="dialog" aria-modal="true" aria-labelledby="approveTitle">
            <div class="modal-content">
                <form method="POST" action="">
                    @csrf
                    <div class="modal-header"><h3 id="approveTitle">Approve booking</h3><button type="button" class="close-modal" aria-label="Close">&times;</button></div>
                    <div class="modal-body">
                        <p class="note-inline" data-approve-summary style="margin-bottom:16px"></p>
                        <label class="field"><span class="field-label">Assign staff <span class="req" aria-hidden="true">*</span></span>
                            <select class="form-control" name="staff_id" required></select>
                            <span class="field-hint">Only staff who are free and qualified for this slot are listed.</span>
                        </label>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-ghost" onclick="closeModal('approveModal')">Cancel</button>
                        <button type="submit" class="btn-primary"><i class="fas fa-check"></i> Approve</button>
                    </div>
                </form>
            </div>
        </div>
        <script>
            document.addEventListener('click', function (event) {
                var trigger = event.target.closest('[data-approve]');
                if (!trigger) return;
                var modal = document.getElementById('approveModal');
                var form = modal.querySelector('form');
                var select = form.querySelector('select[name=staff_id]');
                var staff = JSON.parse(trigger.dataset.staff || '[]');
                form.action = trigger.dataset.approve;
                modal.querySelector('[data-approve-summary]').textContent = trigger.dataset.summary;
                select.innerHTML = '';
                select.add(new Option(staff.length ? 'Choose staff' : 'No staff is free at this time', ''));
                staff.forEach(function (member) {
                    select.add(new Option(member.name, member.id, false, String(member.id) === trigger.dataset.current));
                });
                form.querySelector('button[type=submit]').disabled = staff.length === 0;
                openModal('approveModal');
            });
        </script>
    @endif
@endsection
