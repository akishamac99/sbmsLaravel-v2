@extends('layouts.portal')
@section('title', 'My Feedback')

@push('head')
    @vite(['resources/css/customer-feedback.css', 'resources/js/customer-feedback.js'])
@endpush

@section('content')
<div id="feedback-page" class="owner-stack">
    <div class="page-header">
        <h1>Rate Your Experience</h1>
        <p>Review completed appointments and keep your feedback clear and specific.</p>
    </div>

    @if ($errors->any())
        <div class="error-msg">{{ $errors->first() }}</div>
    @endif

    @if ($pending->isNotEmpty())
        <div class="feedback-grid">
            @foreach ($pending as $booking)
                <section class="card feedback-card {{ $highlight === $booking->bookingRef ? 'feedback-card-focus' : '' }}">
                    <div class="card-header">
                        <span>{{ $booking->service_name }}</span>
                        <span class="badge">{{ $booking->bookingRef }}</span>
                    </div>
                    <div class="card-body">
                        <p class="feedback-meta">{{ $booking->staff_name ?? 'Not assigned' }} &bull; {{ $booking->bookingDate->format('M d, Y') }}</p>

                        <form method="POST" action="{{ route('customer.feedback.store', $booking->bookingRef) }}" class="feedback-form">
                            @csrf

                            <div class="form-group">
                                <label>Service Rating</label>
                                <div class="star-rating">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <button type="button" class="star" data-target="service_rating_{{ $booking->bookingID }}" data-value="{{ $i }}">&#9733;</button>
                                    @endfor
                                </div>
                                <input type="hidden" id="service_rating_{{ $booking->bookingID }}" name="service_rating">
                            </div>

                            <div class="form-group">
                                <label>Staff Rating</label>
                                <div class="star-rating">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <button type="button" class="star" data-target="staff_rating_{{ $booking->bookingID }}" data-value="{{ $i }}">&#9733;</button>
                                    @endfor
                                </div>
                                <input type="hidden" id="staff_rating_{{ $booking->bookingID }}" name="staff_rating">
                            </div>

                            <div class="form-group">
                                <label>Your Comment</label>
                                <textarea name="comment" class="form-control" rows="4" maxlength="1000" placeholder="Describe the service result, staff care, and anything that stood out." required></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary">Submit Feedback</button>
                        </form>
                    </div>
                </section>
            @endforeach
        </div>
    @else
        <div class="card">
            <div class="card-body">
                <div class="customer-empty-state">No pending feedback. You can rate appointments after they are marked completed.</div>
            </div>
        </div>
    @endif

    @if ($history->isNotEmpty())
        <div class="card customer-section-spaced">
            <div class="card-header">Previous Feedback</div>
            <div class="card-body">
                <div class="feedback-history">
                    @foreach ($history as $feedback)
                        <article class="feedback-history-item">
                            <div class="feedback-history-head">
                                <div>
                                    <strong>{{ $feedback->service?->serviceName }}</strong>
                                    <div class="feedback-meta">{{ $feedback->staff?->full_name }} &bull; {{ $feedback->created_at->format('M d, Y') }}</div>
                                </div>
                                <div class="feedback-history-stars">{{ str_repeat('★', max(1, (int) $feedback->rating)) }}</div>
                            </div>
                            <p>{{ $feedback->comment }}</p>
                            <small>Service: {{ (int) ($feedback->service_rating ?? $feedback->rating) }}/5 &bull; Staff: {{ (int) ($feedback->staff_rating ?? $feedback->rating) }}/5</small>
                        </article>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
