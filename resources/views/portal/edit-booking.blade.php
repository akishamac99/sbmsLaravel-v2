@extends('layouts.portal')
@section('title', 'Update Booking')

@section('content')
@php
    $currentTime = \Illuminate\Support\Carbon::parse($booking->bookingTime)->format('H:i:s');
@endphp
<div class="owner-stack">
    <div class="back-row">
        <a href="{{ route($role . '.bookings.show', $booking) }}"><i class="fas fa-arrow-left"></i> Back to booking</a>
    </div>

    <div class="page-header">
        <div class="page-title">
            <h1>Update booking</h1>
            <p>{{ $booking->service_name }} &middot; {{ $booking->customer?->full_name }} &middot; Ref {{ $booking->bookingRef }}</p>
        </div>
    </div>

    <form method="POST" action="{{ route($role . '.bookings.update', $booking) }}" class="card" style="max-width:720px" data-warn-unsaved>
        @csrf
        @method('PUT')
        <div class="card-body">
            @if ($errors->any())
                <div class="alert-inline" role="alert">{{ $errors->first() }}</div>
            @endif

            <div class="fields">
                <label class="field"><span class="field-label">Date <span class="req" aria-hidden="true">*</span></span>
                    <input type="date" name="booking_date" class="form-control" min="{{ today()->toDateString() }}"
                           value="{{ old('booking_date', $booking->bookingDate->toDateString()) }}" required>
                </label>
                <label class="field"><span class="field-label">Time <span class="req" aria-hidden="true">*</span></span>
                    <select name="booking_time" class="form-control" required>
                        @foreach ($slotGroups as $period => $options)
                            @if ($options)
                                <optgroup label="{{ $period }}">
                                    @foreach ($options as $value => $label)
                                        <option value="{{ $value }}" @selected(old('booking_time', $currentTime) === $value)>{{ $label }}</option>
                                    @endforeach
                                </optgroup>
                            @endif
                        @endforeach
                    </select>
                </label>
                @if ($canReassign)
                    <label class="field field-full"><span class="field-label">Assigned staff</span>
                        <select name="staff_id" class="form-control">
                            <option value="">Not assigned</option>
                            @foreach ($staffOptions as $member)
                                <option value="{{ $member->getKey() }}" @selected((int) old('staff_id', $booking->staffID) === (int) $member->getKey())>{{ $member->full_name }}</option>
                            @endforeach
                        </select>
                        <span class="field-hint">Only qualified staff are listed. We check they are free at the chosen time when you save.</span>
                    </label>
                @endif
                <label class="field field-full"><span class="field-label">Notes</span>
                    <textarea name="special_request" class="form-control" rows="3" maxlength="500">{{ old('special_request', $booking->special_request) }}</textarea>
                </label>
            </div>

            <div class="form-actions">
                <a href="{{ route($role . '.bookings.show', $booking) }}" class="btn-ghost">Keep as is</a>
                <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Save changes</button>
            </div>
        </div>
    </form>
</div>
@endsection
