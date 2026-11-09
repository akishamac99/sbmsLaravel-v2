@extends('layouts.portal')
@section('title', 'Schedule')

@section('content')
    <div class="page-header">
        <div class="page-title">
            <h1>Schedule</h1>
            <p>Available and reserved appointment slots for {{ \Illuminate\Support\Carbon::parse($date)->format('l, F j, Y') }}.</p>
        </div>
    </div>

    <section class="card">
        <form method="GET" class="toolbar" role="search" aria-label="Choose a date">
            <label class="field" style="margin:0"><span class="sr-only">Date</span>
                <input type="date" name="date" class="form-control" value="{{ $date }}" required>
            </label>
            <button type="submit" class="btn-ghost btn-sm"><i class="fas fa-calendar-day"></i> Show day</button>
            <a class="btn-ghost btn-sm" href="{{ route($role . '.schedule') }}">Today</a>
        </form>

        <p class="muted-text" style="padding:0 20px">Open means the person is free to take a booking. Reserved shows who is booked. Off means outside working hours or a day off.</p>

        @if ($team->isEmpty() || count($rows) === 0)
            <div class="card-body"><p class="muted-text">The salon has no open slots on this day.</p></div>
        @else
            <div class="table-scroll">
                <table class="list-table">
                    <caption class="sr-only">Slots by staff member</caption>
                    <thead>
                        <tr>
                            <th scope="col">Time</th>
                            @foreach ($team as $member)
                                <th scope="col">{{ $member->full_name }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr>
                                <th scope="row" class="nowrap">{{ $row['label'] }}</th>
                                @foreach ($team as $member)
                                    @php $cell = $row['cells'][$member->getKey()]; @endphp
                                    <td>
                                        @if ($cell['state'] === 'reserved')
                                            <a href="{{ route($role . '.bookings.show', $cell['booking']) }}" class="pill p-pend">Reserved</a>
                                            <span class="cell-sub">{{ $cell['booking']->customer?->full_name }} &middot; {{ $cell['booking']->service_name }}</span>
                                        @elseif ($cell['state'] === 'available')
                                            <span class="pill p-conf">Open</span>
                                        @else
                                            <span class="muted-text">Off</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
