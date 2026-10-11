{{-- Weekly hours editor. Expects $days and, optionally, $schedule (availability rows keyed by day). --}}
@php
    $schedule = $schedule ?? collect();
@endphp
<div class="schedule-editor">
    @foreach ($days as $day)
        @php
            $slot = $schedule->get($day);
            $enabled = (bool) old("availability.{$day}.enabled", $slot?->is_available);
        @endphp
        <div class="schedule-row {{ $enabled ? '' : 'is-off' }}" data-schedule-row>
            <label class="check-row">
                <input type="checkbox" name="availability[{{ $day }}][enabled]" value="1" @checked($enabled)
                       onchange="this.closest('[data-schedule-row]').classList.toggle('is-off', !this.checked)">
                {{ $day }}
            </label>
            <div class="schedule-times">
                <input class="form-control" type="time" step="1800" aria-label="{{ $day }} start" name="availability[{{ $day }}][start_time]" value="{{ old("availability.{$day}.start_time", $slot ? substr($slot->start_time, 0, 5) : '09:00') }}">
                <span class="muted-text">to</span>
                <input class="form-control" type="time" step="1800" aria-label="{{ $day }} end" name="availability[{{ $day }}][end_time]" value="{{ old("availability.{$day}.end_time", $slot ? substr($slot->end_time, 0, 5) : '18:00') }}">
            </div>
        </div>
    @endforeach
</div>
