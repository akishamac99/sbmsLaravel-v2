@extends('layouts.portal')
@section('title', 'Customers')

@push('head')
    @vite(['resources/js/portal-forms.js'])
@endpush

@section('content')
    <div class="page-header">
        <div class="page-title">
            <h1>Customers</h1>
            <p>Registered accounts and walk-in customers, with their booking history.</p>
        </div>
        @if ($canEdit)
            <div class="page-actions">
                <button type="button" class="btn-ghost btn-sm" onclick="openModal('statusHelp')"><i class="fas fa-circle-info"></i> About account status</button>
            </div>
        @endif
    </div>

    <section class="card">
        <form method="GET" class="toolbar" role="search">
            <div class="search-box">
                <i class="fas fa-search" aria-hidden="true"></i>
                <input class="form-control" type="search" name="q" value="{{ request('q') }}" placeholder="Search name, email or phone" aria-label="Search customers" data-auto-search>
            </div>
            <select class="form-control" name="type" aria-label="Customer type" data-auto-submit>
                <option value="">All customers</option>
                <option value="registered" @selected(request('type') === 'registered')>Registered</option>
                <option value="walkin" @selected(request('type') === 'walkin')>Walk-in</option>
            </select>
            <select class="form-control" name="status" aria-label="Account status" data-auto-submit>
                <option value="">Any status</option>
                @foreach (['active' => 'Active', 'pending' => 'Not verified', 'inactive' => 'Inactive', 'blocked' => 'Blocked'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @if (request()->hasAny(['q', 'type', 'status']) && array_filter(request()->only(['q', 'type', 'status'])))
                <a class="btn-ghost btn-sm" href="{{ route($role . '.customers') }}">Clear</a>
            @endif
        </form>

        <div class="table-scroll">
            <table class="list-table">
                <thead><tr><th>Customer</th><th>Contact</th><th>Type</th><th>Account</th><th class="num">Bookings</th><th>Last visit</th><th>Since</th>@if ($canEdit)<th class="col-actions"><span class="sr-only">Actions</span></th>@endif</tr></thead>
                <tbody>
                    @forelse ($customers as $customer)
                        <tr>
                            <td><span class="cell-main">{{ $customer->full_name }}</span></td>
                            <td>
                                <span class="cell-main" style="font-weight:500">{{ $customer->contactNumber }}</span>
                                <span class="cell-sub">{{ $customer->contact_email ?? 'No email on file' }}</span>
                            </td>
                            <td>
                                @if ($customer->is_walkin)
                                    <span class="tag tag-walkin"><i class="fas fa-walking" aria-hidden="true"></i> Walk-in</span>
                                @else
                                    <span class="tag tag-online"><i class="fas fa-user" aria-hidden="true"></i> Registered</span>
                                @endif
                            </td>
                            <td>
                                @if ($customer->is_walkin)
                                    <span class="muted-text" title="Walk-in customers do not have a login">No login</span>
                                @elseif ($customer->accountStatus === 'pending')
                                    <span class="pill p-pend">Not verified</span>
                                @else
                                    @php($statusClass = ['active' => 'p-conf', 'inactive' => 'p-pend', 'blocked' => 'p-dec'][$customer->accountStatus] ?? 'p-pend')
                                    <span class="pill {{ $statusClass }}">{{ ucfirst($customer->accountStatus) }}</span>
                                @endif
                            </td>
                            <td class="num">{{ $customer->bookings_count }}</td>
                            <td class="nowrap">{{ $customer->bookings_max_booking_date ? \Illuminate\Support\Carbon::parse($customer->bookings_max_booking_date)->format('M d, Y') : '—' }}</td>
                            <td class="nowrap">{{ $customer->createdAt?->format('M d, Y') }}</td>
                            @if ($canEdit)
                                <td class="col-actions">
                                    @unless ($customer->is_walkin)
                                        <div class="row-menu">
                                            <button type="button" class="btn-menu" onclick="toggleMenu(this)" aria-label="Actions for {{ $customer->full_name }}"><i class="fas fa-ellipsis-v"></i></button>
                                            <div class="menu-dropdown">
                                                @foreach (['active' => ['Activate', 'fa-user-check', 'menu-success'], 'inactive' => ['Deactivate', 'fa-user-slash', ''], 'blocked' => ['Block', 'fa-ban', 'menu-danger']] as $status => [$label, $icon, $class])
                                                    @if ($customer->accountStatus !== $status)
                                                        <form method="POST" action="{{ route($role . '.customers.status', $customer) }}" @if ($status === 'blocked') onsubmit="return confirm('Block {{ $customer->full_name }}? They will not be able to log in or book.')" @endif>
                                                            @csrf
                                                            <input type="hidden" name="accountStatus" value="{{ $status }}">
                                                            <button type="submit" class="{{ $class }}"><i class="fas {{ $icon }}"></i> {{ $label }}</button>
                                                        </form>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </div>
                                    @endunless
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="8" class="empty-state"><i class="fas fa-users"></i>No customers match these filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $customers->links() }}
    </section>

    @if ($canEdit)
        <div class="modal" id="statusHelp" role="dialog" aria-modal="true" aria-labelledby="statusHelpTitle">
            <div class="modal-content">
                <div class="modal-header"><h3 id="statusHelpTitle">Account status</h3><button type="button" class="close-modal" aria-label="Close">&times;</button></div>
                <div class="modal-body">
                    <dl class="kv" style="grid-template-columns:110px 1fr">
                        <dt><span class="pill p-conf">Active</span></dt><dd>Can log in and book online. Every verified account starts here.</dd>
                        <dt><span class="pill p-pend">Inactive</span></dt><dd>Temporarily switched off, for example at the customer's request or for a dormant account. They cannot log in until reactivated; their history is kept.</dd>
                        <dt><span class="pill p-dec">Blocked</span></dt><dd>Barred for misuse, such as repeated no-shows or fake payment proofs. They cannot log in or book. Use this instead of deleting, so their records stay intact.</dd>
                        <dt><span class="pill p-pend">Not verified</span></dt><dd>Signed up but never entered the email code. Set automatically.</dd>
                        <dt><span class="tag tag-walkin">Walk-in</span></dt><dd>Created at the front desk. Walk-ins have no login, so status does not apply. If they later sign up with the same phone or email, their history moves to the new account.</dd>
                    </dl>
                </div>
            </div>
        </div>
    @endif
@endsection
