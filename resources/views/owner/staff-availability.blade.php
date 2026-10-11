@extends('layouts.portal')
@section('title', 'Staff Availability')

@push('head')
    @vite(['resources/js/portal-forms.js'])
@endpush

@section('content')
    @php
        $short = ['Monday' => 'M', 'Tuesday' => 'T', 'Wednesday' => 'W', 'Thursday' => 'T', 'Friday' => 'F', 'Saturday' => 'S', 'Sunday' => 'S'];
        $fmt = fn ($time) => \Illuminate\Support\Carbon::parse($time)->format('g:i A');
    @endphp

    <div class="page-header">
        <div class="page-title">
            <h1>Staff Availability</h1>
            <p>Weekly working hours. Customers and walk-ins can only be booked inside these hours.</p>
        </div>
    </div>

    <section class="card">
        <form method="GET" class="toolbar" role="search">
            <div class="search-box">
                <i class="fas fa-search" aria-hidden="true"></i>
                <input class="form-control" type="search" name="q" value="{{ request('q') }}" placeholder="Search name or specialization" aria-label="Search staff" data-auto-search>
            </div>
            <select class="form-control" name="role" aria-label="Role" data-auto-submit>
                <option value="">All roles</option>
                @foreach (['Staff', 'Manager', 'Owner'] as $roleOption)
                    <option value="{{ $roleOption }}" @selected(request('role') === $roleOption)>{{ $roleOption }}</option>
                @endforeach
            </select>
            @if (array_filter(request()->only(['q', 'role'])))
                <a class="btn-ghost btn-sm" href="{{ route('owner.staff-availability') }}">Clear</a>
            @endif
        </form>

        <div class="table-scroll">
            <table class="list-table">
                <thead><tr><th>Staff</th><th>Working days</th><th>Hours</th><th class="col-actions"><span class="sr-only">Actions</span></th></tr></thead>
                <tbody>
                    @forelse ($staffMembers as $member)
                        @php
                            $byDay = $member->availability->where('is_available', 1)->keyBy('day_of_week');
                            $ranges = $byDay->map(fn ($slot) => $fmt($slot->start_time) . ' – ' . $fmt($slot->end_time))->unique();
                            $record = ['staff_id' => $member->staffID];
                            foreach ($days as $day) {
                                $slot = $byDay->get($day);
                                $record["availability[{$day}][enabled]"] = $slot ? 1 : 0;
                                $record["availability[{$day}][start_time]"] = $slot ? substr($slot->start_time, 0, 5) : '09:00';
                                $record["availability[{$day}][end_time]"] = $slot ? substr($slot->end_time, 0, 5) : '18:00';
                            }
                        @endphp
                        <tr class="{{ $member->is_active ? '' : 'is-inactive' }}">
                            <td><span class="cell-main">{{ $member->full_name }}</span><span class="cell-sub">{{ $member->role }} &middot; {{ $member->specialization ?: 'General' }}</span></td>
                            <td>
                                <span class="day-dots" aria-label="Works {{ $byDay->keys()->implode(', ') ?: 'no days' }}">
                                    @foreach ($days as $day)
                                        <span class="{{ $byDay->has($day) ? 'on' : '' }}" title="{{ $day }}">{{ $short[$day] }}</span>
                                    @endforeach
                                </span>
                            </td>
                            <td>
                                @if ($ranges->isEmpty())
                                    <span class="muted-text">Not scheduled</span>
                                @elseif ($ranges->count() === 1)
                                    {{ $ranges->first() }}
                                @else
                                    <span title="{{ $byDay->map(fn ($slot, $day) => $day . ': ' . $fmt($slot->start_time) . ' – ' . $fmt($slot->end_time))->implode("\n") }}">Varies by day</span>
                                @endif
                            </td>
                            <td class="col-actions">
                                <div class="row-menu">
                                    <button type="button" class="btn-menu" onclick="toggleMenu(this)" aria-label="Actions for {{ $member->full_name }}"><i class="fas fa-ellipsis-v"></i></button>
                                    <div class="menu-dropdown">
                                        <button type="button" data-open-form="scheduleModal" data-action="{{ route('owner.staff-availability.update') }}"
                                                data-record="{{ json_encode($record) }}" data-staff-name="{{ $member->full_name }}"
                                                @if ($openStaffId === (int) $member->staffID) data-autoopen @endif>
                                            <i class="fas fa-clock"></i> Edit hours
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="empty-state"><i class="fas fa-clock"></i>No staff match these filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $staffMembers->links() }}
    </section>

    <div class="modal" id="scheduleModal" role="dialog" aria-modal="true" aria-labelledby="scheduleTitle" data-modal-form
         @if ($errors->any() && old('_modal') === 'scheduleModal') data-reopen @endif>
        <div class="modal-content modal-lg">
            <form method="POST" action="{{ route('owner.staff-availability.update') }}" data-create-action="{{ route('owner.staff-availability.update') }}" data-edit-method="PUT">
                @csrf
                <input type="hidden" name="_method" value="PUT">
                <input type="hidden" name="_modal" value="scheduleModal">
                <input type="hidden" name="_edit_action" value="{{ old('_edit_action') }}">
                <input type="hidden" name="staff_id" value="{{ old('staff_id') }}">
                <div class="modal-header">
                    <h3 id="scheduleTitle" data-title="Working hours" data-title-edit="Working hours">Working hours</h3>
                    <button type="button" class="close-modal" aria-label="Close">&times;</button>
                </div>
                <div class="modal-body" style="padding:0">
                    @if ($errors->any() && old('_modal') === 'scheduleModal')
                        <div class="alert-inline" role="alert" data-server-error style="margin:16px 20px 0">{{ $errors->first() }}</div>
                    @endif
                    @include('partials.schedule-editor', ['days' => $days])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-ghost" onclick="closeModal('scheduleModal')">Cancel</button>
                    <button type="submit" class="btn-primary">Save hours</button>
                </div>
            </form>
        </div>
    </div>
    <script>
        document.addEventListener('click', function (event) {
            var trigger = event.target.closest('[data-staff-name]');
            if (!trigger) return;
            setTimeout(function () {
                document.getElementById('scheduleTitle').textContent = 'Working hours · ' + trigger.dataset.staffName;
                document.querySelectorAll('#scheduleModal [data-schedule-row]').forEach(function (row) {
                    row.classList.toggle('is-off', !row.querySelector('input[type=checkbox]').checked);
                });
            }, 0);
        });
        window.addEventListener('load', function () {
            var auto = document.querySelector('[data-autoopen]');
            if (auto && !document.querySelector('#scheduleModal[data-reopen]')) auto.click();
        });
    </script>
@endsection
