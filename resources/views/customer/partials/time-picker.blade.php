{{-- Time slot picker. Pass $id (unique on the page) and optionally $value (a time already chosen, as H:i:s).
     It reads the date and service from the form it sits in. --}}
<div class="slot-picker" id="{{ $id }}" data-slot-picker data-url="{{ route('customer.slot-availability') }}">
    <input type="hidden" name="booking_time" value="{{ $value ?? '' }}" data-schedule-field>
    <div class="slot-legend" aria-hidden="true">
        <span><i class="slot-dot is-open"></i> Open</span>
        <span><i class="slot-dot is-few"></i> Last spot</span>
        <span><i class="slot-dot is-full"></i> Full</span>
        <span><i class="slot-dot is-selected"></i> Your pick</span>
    </div>
    <p class="slot-status" data-slot-status role="status">Choose a date to see the available times.</p>
    <div class="slot-groups" data-slot-groups></div>
</div>
