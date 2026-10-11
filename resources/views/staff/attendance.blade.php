@extends('layouts.portal')
@section('title', 'Attendance')

@section('content')
    <div class="page-header">
        <div class="page-title">
            <h1>Attendance</h1>
            <p>Mark your approved online bookings as present or no-show. Walk-ins are already checked in.</p>
        </div>
    </div>

    <section class="card">
        <div class="table-scroll">
            <table class="list-table">
                <thead><tr><th>Date</th><th>Customer</th><th>Service</th><th style="text-align:right">Action</th></tr></thead>
                <tbody>
                    @forelse ($bookings as $booking)
                        <tr>
                            <td class="nowrap">
                                <span class="cell-main">{{ $booking->bookingDate->format('M d, Y') }}</span>
                                <span class="cell-sub">{{ \Illuminate\Support\Carbon::parse($booking->bookingTime)->format('g:i A') }}</span>
                            </td>
                            <td><span class="cell-main">{{ $booking->customer?->full_name }}</span><span class="cell-sub">{{ $booking->customer?->contactNumber }}</span></td>
                            <td>{{ $booking->serviceName }}</td>
                            <td style="text-align:right">
                                <form method="POST" action="{{ route('staff.attendance.mark', $booking) }}" style="display:inline-flex;gap:6px">
                                    @csrf
                                    <button type="submit" name="attendance_status" value="present" class="btn-primary btn-sm">Present</button>
                                    <button type="submit" name="attendance_status" value="no-show" class="btn-ghost btn-sm" onclick="return confirm('Mark this customer as a no-show?')">No-show</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="empty-state"><i class="fas fa-clipboard-check"></i>No approved online bookings need attendance.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $bookings->links() }}
    </section>
@endsection
