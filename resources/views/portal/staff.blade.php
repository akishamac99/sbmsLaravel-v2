@extends('layouts.portal')
@section('title', 'Staff Directory')

@section('content')
    <div class="page-header">
        <div class="page-title">
            <h1>Staff Directory</h1>
            <p>Who is on the team and what they specialise in.</p>
        </div>
    </div>
    <section class="card">
        <div class="table-scroll">
            <table class="list-table">
                <thead><tr><th>Name</th><th>Role</th><th>Specialization</th><th>Contact</th><th>Status</th><th class="num">Bookings</th></tr></thead>
                <tbody>
                    @forelse ($staffMembers as $staff)
                        <tr class="{{ $staff->is_active ? '' : 'is-inactive' }}">
                            <td><span class="cell-main">{{ $staff->full_name }}</span><span class="cell-sub">{{ $staff->email }}</span></td>
                            <td><span class="tag">{{ $staff->role }}</span></td>
                            <td>{{ $staff->specialization ?: 'General' }}</td>
                            <td>{{ $staff->contactNumber ?: '—' }}</td>
                            <td><span class="pill {{ $staff->is_active ? 'p-conf' : 'p-cancel' }}">{{ $staff->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td class="num">{{ $staff->bookings_count }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty-state"><i class="fas fa-id-card"></i>No staff members found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $staffMembers->links() }}
    </section>
@endsection
