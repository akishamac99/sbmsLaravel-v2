@extends('layouts.portal')
@section('title', 'Attendance')

@push('head')
    @vite(['resources/js/portal-forms.js'])
@endpush

@section('content')
    <div class="page-header">
        <div class="page-title">
            <h1>Attendance</h1>
            <p>Mark online bookings as present or no-show. Owners and managers can correct a mistaken record. Walk-ins are checked in on arrival.</p>
        </div>
    </div>

    <section class="card">
        <div class="toolbar">
            <nav class="seg" aria-label="Booking type">
                <a href="{{ route($role . '.attendance') }}" class="{{ $type === '' ? 'active' : '' }}">All</a>
                <a href="{{ route($role . '.attendance', ['type' => 'online']) }}" class="{{ $type === 'online' ? 'active' : '' }}">Online bookings</a>
                <a href="{{ route($role . '.attendance', ['type' => 'walkin']) }}" class="{{ $type === 'walkin' ? 'active' : '' }}">Walk-ins</a>
            </nav>
        </div>
        <div class="table-scroll">
            <table class="list-table">
                <thead><tr><th>Date</th><th>Customer</th><th>Service</th><th>Type</th><th>Staff</th><th>Attendance</th><th style="text-align:right">Action</th></tr></thead>
                <tbody>
                    @forelse ($bookings as $booking)
                        <tr>
                            <td class="nowrap">
                                <span class="cell-main">{{ $booking->bookingDate->format('M d, Y') }}</span>
                                <span class="cell-sub">{{ \Illuminate\Support\Carbon::parse($booking->bookingTime)->format('g:i A') }}</span>
                            </td>
                            <td><span class="cell-main">{{ $booking->customer?->full_name }}</span><span class="cell-sub">{{ $booking->customer?->contactNumber }}</span></td>
                            <td>{{ $booking->serviceName }}</td>
                            <td>@include('partials.booking-type', ['booking' => $booking])</td>
                            <td>{{ $booking->staff?->full_name ?? 'Unassigned' }}</td>
                            <td>
                                @if ($booking->is_walkin)
                                    <span class="pill p-conf">{{ $booking->bookingStatus === 'approved' ? 'In service' : 'Checked in' }}</span>
                                @elseif ($booking->bookingStatus === 'approved')
                                    <span class="pill p-pend">Awaiting</span>
                                @else
                                    @include('partials.status-badge', ['status' => $booking->attendance_status])
                                @endif
                            </td>
                            <td style="text-align:right">
                                @if ($booking->bookingStatus === 'approved' && $booking->is_walkin && $canCompleteWalkins)
                                    <form method="POST" action="{{ route($role . '.walkin.complete', $booking) }}" onsubmit="return confirm('Mark this walk-in as completed?')">
                                        @csrf
                                        <button class="btn-outline btn-sm"><i class="fas fa-check"></i> Mark completed</button>
                                    </form>
                                @elseif ($booking->bookingStatus === 'approved' && ! $booking->is_walkin && $canMark)
                                    <form method="POST" action="{{ route($role . '.attendance.update', $booking) }}" style="display:inline-flex;gap:6px">
                                        @csrf
                                        <button class="btn-primary btn-sm" name="attendance_status" value="present">Present</button>
                                        <button class="btn-ghost btn-sm" name="attendance_status" value="no-show" onclick="return confirm('Mark this customer as a no-show?')">No-show</button>
                                    </form>
                                @elseif (! $booking->is_walkin && in_array($booking->bookingStatus, ['completed', 'no-show'], true) && $canMark)
                                    @php $next = $booking->bookingStatus === 'completed' ? 'no-show' : 'present'; @endphp
                                    <form method="POST" action="{{ route($role . '.attendance.update', $booking) }}"
                                          onsubmit="return confirm('Correct this record to {{ $next === 'present' ? 'Present' : 'No-show' }}?')">
                                        @csrf
                                        <button class="btn-outline btn-sm" name="attendance_status" value="{{ $next }}">
                                            <i class="fas fa-rotate-left"></i> Change to {{ $next === 'present' ? 'Present' : 'No-show' }}
                                        </button>
                                    </form>
                                @else
                                    <span class="muted-text">&mdash;</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="empty-state"><i class="fas fa-clipboard-check"></i>No attendance records yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $bookings->links() }}
    </section>
@endsection
