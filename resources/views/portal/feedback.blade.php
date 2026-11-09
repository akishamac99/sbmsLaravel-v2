@extends('layouts.portal')
@section('title', 'Feedback')
@push('head') @include('partials.a11y-styles') @endpush

@section('content')
    <div class="page-header"><h1>Customer feedback</h1><p>Ratings and comments from completed bookings.</p></div>

    <form method="GET" class="card" role="search" aria-label="Filter feedback"><div class="card-body form-grid">
        <div class="form-group"><label for="q">Search service or comment</label>
            <input id="q" class="form-control" type="search" name="q" maxlength="100" value="{{ request('q') }}"></div>
        <div class="form-group"><label for="rating">Rating</label>
            <select id="rating" class="form-control" name="rating"><option value="">All ratings</option>
                @foreach (range(5, 1) as $r)<option value="{{ $r }}" @selected((string) request('rating') === (string) $r)>{{ $r }} star{{ $r > 1 ? 's' : '' }}</option>@endforeach
            </select></div>
        <div class="form-actions"><button class="btn-primary" type="submit">Apply filters</button></div>
    </div></form>

    @if ($loadError)
        <p class="alert-inline" role="alert">Feedback could not be loaded. <a href="{{ request()->fullUrl() }}">Retry</a></p>
    @else
        <section class="card" aria-labelledby="fb-h"><div class="card-header"><span id="fb-h">Feedback records</span></div><div class="card-body owner-table-wrap">
            <table class="table">
                <caption class="sr-only">Customer feedback, newest first</caption>
                <thead><tr><th scope="col">Date</th><th scope="col">Customer</th><th scope="col">Service</th><th scope="col">Staff</th><th scope="col">Rating</th><th scope="col">Comment</th></tr></thead>
                <tbody>
                @forelse ($feedback as $row)
                    <tr>
                        <td>{{ \Illuminate\Support\Carbon::parse($row->created_at)->format('M j, Y') }}</td>
                        <td>{{ $row->customer_first }}</td><td>{{ $row->serviceName }}</td><td>{{ $row->staff_name }}</td>
                        <td><span aria-label="{{ (int) $row->rating }} out of 5 stars">{{ (int) $row->rating }}/5</span></td>
                        <td>{{ $row->comment ?: '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6">No feedback records found.</td></tr>
                @endforelse
                </tbody>
            </table>
            {{ $feedback->links() }}
        </div></section>
    @endif
@endsection
