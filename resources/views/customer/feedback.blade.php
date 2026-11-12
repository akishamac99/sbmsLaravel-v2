@extends('layouts.portal')
@section('title', 'My Feedback')

@push('head')
    @vite(['resources/css/customer-feedback.css', 'resources/js/customer-feedback.js'])
@endpush

@section('content')
@php
    $given = $history->count();
    $average = $given ? round($history->avg('rating'), 1) : null;
    $words = [1 => 'Poor', 2 => 'Fair', 3 => 'Good', 4 => 'Very good', 5 => 'Excellent'];
@endphp
<div id="feedback-page" class="owner-stack">
    <div class="page-header">
        <h1>Rate Your Experience</h1>
        <p>Tell us how your completed appointments went. It helps us improve and helps other clients choose.</p>
    </div>

    @if ($errors->any())
        <div class="error-msg" role="alert">{{ $errors->first() }}</div>
    @endif

    <div class="fb-summary">
        <div class="fb-stat"><strong>{{ $pending->count() }}</strong><span>Waiting for your rating</span></div>
        <div class="fb-stat"><strong>{{ $given }}</strong><span>Feedback sent</span></div>
        <div class="fb-stat"><strong>{{ $average ? $average.' / 5' : '-' }}</strong><span>Your average rating</span></div>
    </div>

    <div class="fb-section-title"><h2>To rate</h2><span>Completed appointments without feedback</span></div>

    @if ($pending->isNotEmpty())
        <div class="feedback-grid">
            @foreach ($pending as $booking)
                <section class="card feedback-card {{ $highlight === $booking->bookingRef ? 'feedback-card-focus' : '' }}" @if ($highlight === $booking->bookingRef) id="rate-focus" @endif>
                    <div class="card-header">
                        <span>{{ $booking->service_name }}</span>
                        <span class="badge">{{ $booking->bookingRef }}</span>
                    </div>
                    <div class="card-body">
                        <p class="feedback-meta">With {{ $booking->staff_name ?? 'our team' }} &bull; {{ $booking->bookingDate->format('M d, Y') }}</p>

                        <form method="POST" action="{{ route('customer.feedback.store', $booking->bookingRef) }}" class="feedback-form" data-warn-unsaved novalidate>
                            @csrf

                            @foreach (['service_rating' => 'How was the service?', 'staff_rating' => 'How was your stylist / staff?'] as $field => $label)
                                <fieldset class="fb-group">
                                    <legend>{{ $label }}</legend>
                                    <div class="fb-rate-row">
                                        <div class="star-rating">
                                            @for ($i = 5; $i >= 1; $i--)
                                                <input type="radio" name="{{ $field }}" id="{{ $field }}_{{ $booking->bookingID }}_{{ $i }}" value="{{ $i }}">
                                                <label for="{{ $field }}_{{ $booking->bookingID }}_{{ $i }}" title="{{ $i }} star{{ $i > 1 ? 's' : '' }}"><span aria-hidden="true">&#9733;</span><span class="sr-only">{{ $i }} star{{ $i > 1 ? 's' : '' }} - {{ $words[$i] }}</span></label>
                                            @endfor
                                        </div>
                                        <span class="fb-rate-word" aria-live="polite"></span>
                                    </div>
                                </fieldset>
                            @endforeach

                            <div class="form-group">
                                <label for="comment_{{ $booking->bookingID }}">Your comment</label>
                                <textarea id="comment_{{ $booking->bookingID }}" name="comment" class="form-control" rows="4" maxlength="1000" placeholder="What went well? Anything we could do better?" required></textarea>
                                <div class="fb-comment-meta"><span>Be specific - it helps the most.</span><span class="fb-count">0 / 1000</span></div>
                            </div>

                            <p class="fb-error" role="alert" hidden></p>
                            <button type="submit" class="btn btn-primary">Submit feedback</button>
                        </form>
                    </div>
                </section>
            @endforeach
        </div>
    @else
        <div class="card"><div class="card-body">
            <div class="customer-empty-state">You're all caught up. You can rate an appointment once it is marked completed.</div>
        </div></div>
    @endif

    @if ($history->isNotEmpty())
        <div class="fb-section-title customer-section-spaced"><h2>Your previous feedback</h2><span>{{ $given }} {{ \Illuminate\Support\Str::plural('entry', $given) }}</span></div>
        <div class="feedback-history">
            @foreach ($history as $feedback)
                @php $r = max(1, min(5, (int) $feedback->rating)); @endphp
                <article class="feedback-history-item">
                    <div class="feedback-history-head">
                        <div>
                            <strong>{{ $feedback->service?->serviceName }}</strong>
                            <div class="feedback-meta">{{ $feedback->staff?->full_name }} &bull; {{ $feedback->created_at->format('M d, Y') }}</div>
                        </div>
                        <div class="feedback-history-stars" role="img" aria-label="{{ $r }} out of 5 stars">{{ str_repeat('★', $r) }}<span class="off">{{ str_repeat('★', 5 - $r) }}</span></div>
                    </div>
                    <p>{{ $feedback->comment }}</p>
                    <div class="fb-chips">
                        <span class="fb-chip">Service {{ (int) ($feedback->service_rating ?? $feedback->rating) }}/5</span>
                        <span class="fb-chip">Staff {{ (int) ($feedback->staff_rating ?? $feedback->rating) }}/5</span>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</div>
@if ($highlight !== '')
    <script>document.getElementById('rate-focus')?.scrollIntoView({block: 'center'});</script>
@endif
@endsection
