{{-- A time picker. Pass $id (must be unique on the page) and $groups (from TimeSlots::groups()). --}}
<div class="time-picker-wrapper" id="{{ $id }}">
    <input type="hidden" name="booking_time" data-schedule-field>
    <div class="time-picker-display">
        <i class="fas fa-clock"></i>
        <span>Select time</span>
        <i class="fas fa-chevron-down time-picker-chevron"></i>
    </div>
    <div class="time-picker-dropdown">
        @foreach ($groups as $period => $slots)
            <div class="time-period-group">
                <div class="time-period-label">{{ $period }}</div>
                <div class="time-slots-grid">
                    @foreach ($slots as $value => $label)
                        <button type="button" class="time-slot-btn" data-value="{{ $value }}" data-label="{{ $label }}">{{ $label }}</button>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>
