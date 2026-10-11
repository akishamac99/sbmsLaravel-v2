@extends('layouts.portal')
@section('title', 'Reschedule Booking')

@section('content')
@php
    $currentTime = \Illuminate\Support\Carbon::parse($booking->bookingTime)->format('H:i:s');
@endphp
<div class="owner-stack">
    <div class="back-row">
        <a href="{{ route('customer.bookings.show', $booking->bookingRef) }}"><i class="fas fa-arrow-left"></i> Back to booking</a>
    </div>

    <div class="page-header">
        <div class="page-title">
            <h1>Reschedule</h1>
            <p>{{ $booking->service_name }} &middot; now on {{ $booking->appointment_at->format('l, F j \a\t g:i A') }}</p>
        </div>
    </div>

    <form method="POST" action="{{ route('customer.bookings.reschedule.store', $booking->bookingRef) }}" class="card" style="max-width:720px" id="rescheduleForm"
          data-availability-url="{{ route('customer.staff-availability') }}"
          data-service-id="{{ $booking->serviceID }}"
          data-booking="{{ $booking->bookingRef }}"
          data-old-staff="{{ old('staff_id', $booking->staffID) }}">
        @csrf
        <div class="card-body">
            @if ($errors->any())
                <div class="alert-inline" role="alert">{{ $errors->first() }}</div>
            @endif

            <div class="note-inline" style="margin-bottom:20px">
                <i class="fas fa-circle-info"></i>
                You can change or cancel this booking until <strong>{{ $booking->change_deadline->format('M d, g:i A') }}</strong>
                ({{ $windowHours }} hours before the appointment).
                Reschedules left: <strong>{{ $booking->reschedules_left }}</strong>.
                @if ($booking->bookingStatus === 'approved')
                    The salon will confirm the new time again.
                @endif
            </div>

            <div class="fields">
                <label class="field"><span class="field-label">New date <span class="req" aria-hidden="true">*</span></span>
                    <input type="date" name="booking_date" class="form-control" min="{{ $minDate }}" max="{{ $maxDate }}"
                           value="{{ old('booking_date', max($minDate, $booking->bookingDate->toDateString())) }}" required>
                </label>
                <label class="field"><span class="field-label">New time <span class="req" aria-hidden="true">*</span></span>
                    <select name="booking_time" class="form-control" required>
                        @foreach ($timeSlots as $value => $label)
                            <option value="{{ $value }}" @selected(old('booking_time', $currentTime) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field field-full"><span class="field-label">Staff <span class="req" aria-hidden="true">*</span></span>
                    <select name="staff_id" class="form-control" required>
                        <option value="">Choose a date and time first</option>
                    </select>
                    <span class="field-hint" data-staff-hint>Only staff who are free and qualified at that time are listed.</span>
                </label>
            </div>

            <div class="form-actions">
                <a href="{{ route('customer.bookings.show', $booking->bookingRef) }}" class="btn-ghost">Keep current time</a>
                <button type="submit" class="btn-primary"><i class="fas fa-calendar-check"></i> Move booking</button>
            </div>
        </div>
    </form>
</div>

<script>
    (function () {
        var form = document.getElementById('rescheduleForm');
        var date = form.querySelector('[name=booking_date]');
        var time = form.querySelector('[name=booking_time]');
        var staff = form.querySelector('[name=staff_id]');
        var hint = form.querySelector('[data-staff-hint]');
        var preferred = form.dataset.oldStaff || '';
        var ticket = 0;

        function load() {
            var mine = ++ticket;
            if (staff.value) preferred = staff.value;
            if (!date.value || !time.value) return;
            staff.disabled = true;
            staff.innerHTML = '<option value="">Checking who is free…</option>';
            var query = new URLSearchParams({ date: date.value, time: time.value, service_id: form.dataset.serviceId, booking: form.dataset.booking });
            fetch(form.dataset.availabilityUrl + '?' + query, { headers: { Accept: 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (mine !== ticket) return;
                    var list = data.success ? data.staff : [];
                    staff.innerHTML = '';
                    staff.add(new Option(list.length ? 'Choose staff' : 'Nobody is free at this time', ''));
                    list.forEach(function (s) {
                        staff.add(new Option(s.fullname + ' · ' + s.specialization, s.staffID, false, String(s.staffID) === String(preferred)));
                    });
                    staff.disabled = list.length === 0;
                    hint.textContent = list.length ? list.length + ' staff free at this time.' : (data.message || 'Try another date or time.');
                })
                .catch(function () {
                    staff.innerHTML = '<option value="">Could not check availability</option>';
                    hint.textContent = 'Please choose the date or time again.';
                });
        }

        date.addEventListener('change', load);
        time.addEventListener('change', load);
        load();
    })();
</script>
@endsection
