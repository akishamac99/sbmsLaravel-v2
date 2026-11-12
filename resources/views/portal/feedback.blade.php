@extends('layouts.portal')
@section('title', 'Feedback')
@push('head')
    @include('partials.a11y-styles')
    @vite(['resources/css/customer-feedback.css'])
@endpush

@section('content')
    <div class="page-header"><h1>Customer feedback</h1><p>{{ $role === 'staff' ? 'What clients said about your work.' : 'Ratings and comments from completed bookings.' }}</p></div>

    @if ($loadError)
        <p class="alert-inline" role="alert">Feedback could not be loaded. <a href="{{ request()->fullUrl() }}">Retry</a></p>
    @else
        @if ($total > 0)
            <section class="card" aria-label="Rating summary"><div class="card-body fb-overview">
                <div class="fb-avg">
                    <strong>{{ number_format($average, 1) }}</strong>
                    <div class="stars" role="img" aria-label="Average {{ number_format($average, 1) }} out of 5">{{ str_repeat('★', (int) round($average)) }}<span class="off">{{ str_repeat('★', 5 - (int) round($average)) }}</span></div>
                    <small>{{ $total }} {{ \Illuminate\Support\Str::plural('review', $total) }}</small>
                </div>
                <div class="fb-bars">
                    @foreach (range(5, 1) as $r)
                        @php $n = $distribution[$r] ?? 0; @endphp
                        <a class="fb-bar" href="{{ request()->fullUrlWithQuery(['rating' => $r, 'page' => null]) }}" aria-label="{{ $n }} reviews with {{ $r }} stars, filter">
                            <span>{{ $r }} ★</span>
                            <span class="track"><span class="fill" style="width: {{ $total ? round($n / $total * 100) : 0 }}%"></span></span>
                            <span>{{ $n }}</span>
                        </a>
                    @endforeach
                </div>
            </div></section>
        @endif

        <form method="GET" class="card" role="search" aria-label="Filter feedback"><div class="card-body fb-filter">
            <div class="form-group"><label for="q">Search service or comment</label>
                <input id="q" class="form-control" type="search" name="q" maxlength="100" value="{{ request('q') }}" placeholder="e.g. haircut, friendly"></div>
            <div class="form-group"><label for="rating">Rating</label>
                <select id="rating" class="form-control" name="rating"><option value="">All ratings</option>
                    @foreach (range(5, 1) as $r)<option value="{{ $r }}" @selected((string) request('rating') === (string) $r)>{{ $r }} star{{ $r > 1 ? 's' : '' }}</option>@endforeach
                </select></div>
            <div class="fb-filter-actions">
                <button class="btn-primary" type="submit">Apply filters</button>
                @if (request()->filled('q') || request()->filled('rating'))<a class="btn-secondary" href="{{ route(request()->route()->getName()) }}">Clear</a>@endif
            </div>
        </div></form>

        <section aria-labelledby="fb-h">
            <h2 id="fb-h" class="sr-only">Feedback records, newest first</h2>
            <div class="fb-list">
                @forelse ($feedback as $row)
                    @php $r = max(1, min(5, (int) $row->rating)); @endphp
                    <article class="fb-item">
                        <div class="fb-item-head">
                            <div><strong>{{ $row->customer_first }}</strong> <span class="when">&bull; {{ \Illuminate\Support\Carbon::parse($row->created_at)->format('M j, Y') }}</span></div>
                            <div class="fb-stars" role="img" aria-label="{{ $r }} out of 5 stars">{{ str_repeat('★', $r) }}<span class="off">{{ str_repeat('★', 5 - $r) }}</span></div>
                        </div>
                        @if ($row->comment)<p>{{ $row->comment }}</p>@else<p class="empty">No comment left.</p>@endif
                        <div class="fb-chips">
                            <span class="fb-chip">{{ $row->serviceName }}</span>
                            @if ($role !== 'staff')<span class="fb-chip">with {{ $row->staff_name }}</span>@endif
                            @if ($row->service_rating)<span class="fb-chip">Service {{ (int) $row->service_rating }}/5</span>@endif
                            @if ($row->staff_rating)<span class="fb-chip">Staff {{ (int) $row->staff_rating }}/5</span>@endif
                        </div>
                    </article>
                @empty
                    <div class="card"><div class="card-body fb-empty">No feedback found{{ request()->filled('q') || request()->filled('rating') ? ' for these filters' : ' yet' }}.</div></div>
                @endforelse
            </div>
            <div style="margin-top:16px">{{ $feedback->links() }}</div>
        </section>
    @endif
@endsection
