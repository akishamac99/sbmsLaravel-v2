@extends('layouts.portal')
@section('title', 'Permissions')

@section('content')
    @php
        // What each manager permission actually unlocks. Only these switches change anything in the app.
        $sections = [
            'bookings' => ['Bookings', 'See all bookings and their details.', 'edit', 'Approve, decline and cancel bookings'],
            'walkin' => ['Walk-ins', 'See today\'s walk-ins.', 'create', 'Check in and complete walk-ins'],
            'payments' => ['Payments', 'See payments and proof screenshots.', 'edit', 'Confirm or reject payment proofs'],
            'attendance' => ['Attendance', 'See attendance records.', 'edit', 'Mark customers present or no-show'],
            'customers' => ['Customers', 'See the customer list and contact details.', null, null],
            'services' => ['Services', 'See the service menu and prices.', null, null],
            'staff' => ['Staff directory', 'See team members and specializations.', null, null],
            'reports' => ['Reports', 'See revenue and performance reports.', null, null],
        ];
    @endphp

    @include('partials.settings-header', ['subtitle' => 'Account, booking rules, manager access and activity history.'])

    <form method="POST" action="{{ route('owner.permissions.update') }}" class="card">
        @csrf @method('PUT')
        <div class="card-header"><span>Manager access <span class="card-sub">Choose what managers can open and do. Owners always have full access.</span></span></div>
        <div class="table-scroll">
            <table class="list-table perm-table">
                <thead><tr><th>Section</th><th>Can open</th><th>Can change</th></tr></thead>
                <tbody>
                    @foreach ($sections as $module => [$label, $viewText, $action, $actionText])
                        @continue(! in_array($module, $modules, true))
                        @php($permission = $permissions->get($module))
                        <tr data-perm-row>
                            <td><span class="cell-main">{{ $label }}</span><span class="cell-sub">{{ $viewText }}</span></td>
                            <td><input type="checkbox" name="permissions[{{ $module }}][view]" value="1" aria-label="Managers can open {{ $label }}" @checked($permission?->can_view) data-perm-view></td>
                            <td>
                                @if ($action)
                                    <label class="check-row" style="justify-content:center;font-size:.8rem">
                                        <input type="checkbox" name="permissions[{{ $module }}][{{ $action }}]" value="1" aria-label="{{ $actionText }}" @checked($permission?->{'can_' . $action}) data-perm-action>
                                        <span>{{ $actionText }}</span>
                                    </label>
                                @else
                                    <span class="muted-text">View only</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="form-actions" style="padding:0 20px 20px">
            <button class="btn-primary" type="submit">Save access</button>
        </div>
    </form>
    <script>
        document.querySelectorAll('[data-perm-row]').forEach(function (row) {
            var view = row.querySelector('[data-perm-view]');
            var action = row.querySelector('[data-perm-action]');
            if (!action) return;
            function sync() { action.disabled = !view.checked; if (!view.checked) action.checked = false; }
            view.addEventListener('change', sync);
            sync();
        });
    </script>
@endsection
