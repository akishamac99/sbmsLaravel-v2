@extends('layouts.portal')
@section('title', 'Archived Accounts')
@push('head') @include('partials.a11y-styles') @endpush

@section('content')
    <div class="page-header"><h1>Archived accounts</h1><p>Restore an archived staff account, or permanently delete accounts that are no longer needed.</p></div>

    <section class="card" aria-labelledby="archived-staff-h"><div class="card-header"><span id="archived-staff-h">Archived staff accounts</span></div>
    <div class="card-body owner-table-wrap">
        <table class="table">
            <caption class="sr-only">Archived staff accounts and the actions available for each</caption>
            <thead><tr><th scope="col">Name</th><th scope="col">Role</th><th scope="col">Username</th><th scope="col">Actions</th></tr></thead>
            <tbody>
            @forelse ($archivedStaff as $staff)
                <tr>
                    <td>{{ $staff->full_name }}</td><td>{{ $staff->role }}</td><td>{{ $staff->username }}</td>
                    <td>
                        <a class="btn-outline btn-sm" href="{{ route('owner.accounts.restore.confirm', $staff) }}">Restore<span class="sr-only"> {{ $staff->full_name }}</span></a>
                        <a class="btn-ghost btn-sm" href="{{ route('owner.accounts.staff.confirm', $staff) }}">Delete<span class="sr-only"> {{ $staff->full_name }}</span></a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4">No archived staff accounts.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div></section>

    <section class="card" aria-labelledby="removable-h"><div class="card-header"><span id="removable-h">Inactive or blocked customer accounts</span></div>
    <div class="card-body owner-table-wrap">
        <table class="table">
            <caption class="sr-only">Customer accounts that are not active and can be deleted if they have no bookings</caption>
            <thead><tr><th scope="col">Name</th><th scope="col">Email</th><th scope="col">Status</th><th scope="col">Actions</th></tr></thead>
            <tbody>
            @forelse ($removableCustomers as $customer)
                <tr>
                    <td>{{ $customer->full_name }}</td><td>{{ $customer->email }}</td><td>{{ ucfirst($customer->accountStatus) }}</td>
                    <td><a class="btn-ghost btn-sm" href="{{ route('owner.accounts.customer.confirm', $customer) }}">Delete<span class="sr-only"> {{ $customer->full_name }}</span></a></td>
                </tr>
            @empty
                <tr><td colspan="4">No inactive or blocked customer accounts.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div></section>
@endsection
