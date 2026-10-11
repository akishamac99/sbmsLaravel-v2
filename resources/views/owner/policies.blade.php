@extends('layouts.portal')
@section('title', 'Policies')

@section('content')
    @php($value = fn ($key, $default) => old($key, $policies->get($key)?->policy_value ?? $default))

    @include('partials.settings-header', ['subtitle' => 'Account, booking rules, manager access and activity history.'])

    <form method="POST" action="{{ route('owner.policies.update') }}" class="card">
        @csrf @method('PUT')
        <div class="card-header"><span>Booking policies <span class="card-sub">Rules applied to every online booking and walk-in.</span></span></div>
        <div class="card-body">
            @if ($errors->any())
                <div class="alert-inline" role="alert">{{ $errors->first() }}</div>
            @endif

            <div class="form-section">
                <div class="form-section-title">Payments</div>
                <div class="fields">
                    <label class="field">Downpayment
                        <span class="input-suffix"><input class="form-control" type="number" min="0" max="100" step="0.01" name="downpayment_percent" value="{{ $value('downpayment_percent', 20) }}" required><span>%</span></span>
                        <span class="field-hint">Share of the price paid up front when a customer chooses partial payment.</span>
                    </label>
                </div>
            </div>

            <div class="form-section">
                <div class="form-section-title">Bookings</div>
                <div class="fields">
                    <label class="field">Cancellation window
                        <span class="input-suffix"><input class="form-control" type="number" min="0" max="720" name="cancellation_window_hours" value="{{ $value('cancellation_window_hours', 24) }}" required><span>hours</span></span>
                        <span class="field-hint">Customers can cancel or reschedule online until this many hours before the appointment. After that they must call the salon.</span>
                    </label>
                    <label class="field">Reschedules allowed
                        <span class="input-suffix"><input class="form-control" type="number" min="0" max="20" name="max_reschedule_count" value="{{ $value('max_reschedule_count', 2) }}" required><span>times</span></span>
                        <span class="field-hint">How many times a customer can move one booking. Each move needs your approval again. 0 turns rescheduling off.</span>
                    </label>
                    <label class="field">Bookings per time slot
                        <span class="input-suffix"><input class="form-control" type="number" min="1" max="100" name="max_bookings_per_slot" value="{{ $value('max_bookings_per_slot', 10) }}" required><span>max</span></span>
                        <span class="field-hint">Across the whole salon, for any single 30-minute slot.</span>
                    </label>
                </div>
            </div>

            <div class="form-section">
                <div class="form-section-title">Walk-ins</div>
                <input type="hidden" name="walkins_allowed" value="0">
                <label class="check-row"><input type="checkbox" name="walkins_allowed" value="1" @checked((string) $value('walkins_allowed', '1') === '1')> Accept walk-in customers at the front desk</label>
            </div>

            <div class="form-actions">
                <button class="btn-primary" type="submit">Save policies</button>
            </div>
        </div>
    </form>
@endsection
