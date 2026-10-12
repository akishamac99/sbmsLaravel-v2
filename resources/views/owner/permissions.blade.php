@extends('layouts.portal')
@section('title', 'Permissions')

@section('content')
    @php
        // Every switch below is enforced by the app. 'view' is the "Can open" box; the others are listed per section.
        $sections = [
            'bookings' => ['Bookings', 'See all bookings and their details.', ['edit' => 'Approve, decline and cancel bookings']],
            'walkin' => ['Walk-ins', 'See today\'s walk-ins.', ['create' => 'Check in and complete walk-ins']],
            'payments' => ['Payments', 'See payments and proof screenshots.', ['edit' => 'Confirm or reject payment proofs']],
            'attendance' => ['Attendance', 'See attendance records.', ['edit' => 'Mark customers present or no-show, and correct mistakes']],
            'customers' => ['Customers', 'See the customer list and contact details.', ['edit' => 'Activate, deactivate or block customer accounts']],
            'services' => ['Services', 'See the service menu and prices.', ['create' => 'Add new services', 'edit' => 'Edit services and switch them on or off']],
            'staff' => ['Staff directory', 'See team members and specializations.', ['create' => 'Add Staff accounts', 'edit' => 'Edit Staff accounts and switch them on or off', 'delete' => 'Archive Staff accounts']],
            'reports' => ['Reports', 'See revenue and performance reports.', []],
        ];
    @endphp

    @include('partials.settings-header', ['subtitle' => 'Account, booking rules, manager access and activity history.'])

    <form method="POST" action="{{ route('owner.permissions.update') }}" class="card">
        @csrf @method('PUT')
        <div class="card-header"><span>Manager access <span class="card-sub">Choose what managers can open and do. Owners always have full access.</span></span></div>
        <div class="table-scroll">
            <table class="list-table perm-table">
                <thead><tr><th>Section</th><th>Can open</th><th>Can do</th></tr></thead>
                <tbody>
                    @foreach ($sections as $module => [$label, $viewText, $actions])
                        @continue(! in_array($module, $modules, true))
                        @php($permission = $permissions->get($module))
                        <tr data-perm-row>
                            <td><span class="cell-main">{{ $label }}</span><span class="cell-sub">{{ $viewText }}</span></td>
                            <td><input type="checkbox" name="permissions[{{ $module }}][view]" value="1" aria-label="Managers can open {{ $label }}" @checked($permission?->can_view) data-perm-view></td>
                            <td>
                                @forelse ($actions as $action => $actionText)
                                    <label class="check-row" style="font-size:.8rem">
                                        <input type="checkbox" name="permissions[{{ $module }}][{{ $action }}]" value="1" aria-label="{{ $actionText }}" @checked($permission?->{'can_' . $action}) data-perm-action>
                                        <span>{{ $actionText }}</span>
                                    </label>
                                @empty
                                    <span class="muted-text">View only</span>
                                @endforelse
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="muted-text" style="padding:0 20px">Managers can only add, edit and archive <strong>Staff</strong> accounts. They can never change the owner, other managers or their own account.</p>
        <div class="form-actions" style="padding:12px 20px 20px">
            <button class="btn-primary" type="submit">Save access</button>
        </div>
    </form>
    <script>
        document.querySelectorAll('[data-perm-row]').forEach(function (row) {
            var view = row.querySelector('[data-perm-view]');
            var actions = row.querySelectorAll('[data-perm-action]');
            if (!actions.length) return;
            function sync() {
                actions.forEach(function (box) { box.disabled = !view.checked; if (!view.checked) box.checked = false; });
            }
            view.addEventListener('change', sync);
            sync();
        });
    </script>
@endsection
