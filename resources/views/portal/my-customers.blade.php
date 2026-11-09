@extends('layouts.portal')
@section('title', 'My Customers')

@section('content')
    <div class="page-header">
        <div class="page-title">
            <h1>My Customers</h1>
            <p>Customers you have been booked with, and their history with you.</p>
        </div>
    </div>

    <section class="card">
        <div class="table-scroll">
            <table class="list-table">
                <thead><tr><th scope="col">Customer</th><th scope="col">Contact</th><th scope="col" class="num">Visits with you</th><th scope="col"><span class="sr-only">Open</span></th></tr></thead>
                <tbody>
                    @forelse ($customers as $customer)
                        <tr>
                            <td><span class="cell-main">{{ $customer->full_name }}</span></td>
                            <td>{{ $customer->contactNumber }}</td>
                            <td class="num">{{ $customer->visits_count }}</td>
                            <td><a class="btn-ghost btn-sm" href="{{ route('staff.customers.show', $customer) }}">View history</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="muted-text">No customers yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body">{{ $customers->links() }}</div>
    </section>
@endsection
