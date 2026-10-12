@extends('layouts.portal')
@section('title', 'My Availability')

@section('content')
    @include('partials.settings-header', ['subtitle' => 'Your account and working hours.'])

    @if ($pendingRequest)
        <div class="alert-inline" role="status" style="margin-bottom:16px">
            <strong>Waiting for the owner.</strong>
            You sent a change request on {{ $pendingRequest->created_at->format('M d, Y g:i A') }}.
            Your current hours stay active until it is approved. Reason you gave: &ldquo;{{ $pendingRequest->reason }}&rdquo;
        </div>
    @endif

    <section class="card">
        <div class="card-header"><span>Weekly working hours <span class="card-sub">Changes are sent to the owner for approval. Customers can only book you during your approved hours.</span></span></div>
        <form method="POST" action="{{ route('staff.availability.update') }}">
            @csrf
            @if ($errors->any())
                <div class="alert-inline" role="alert" style="margin:16px 20px 0">{{ $errors->first() }}</div>
            @endif
            @include('partials.schedule-editor', ['days' => $days, 'schedule' => $schedule])
            <div style="padding:0 20px">
                <label class="form-label" for="reason">Reason for the change</label>
                <textarea class="form-control" id="reason" name="reason" rows="2" maxlength="255" required placeholder="For example: moving my day off from Monday to Wednesday">{{ old('reason') }}</textarea>
            </div>
            <div class="form-actions" style="padding:16px 20px 20px">
                <button class="btn-primary" type="submit" @disabled($pendingRequest)>Send for approval</button>
            </div>
        </form>
    </section>

    @if ($recentRequests->isNotEmpty())
        <section class="card">
            <div class="card-header"><span>Recent requests</span></div>
            <div class="card-body">
                @foreach ($recentRequests as $past)
                    <div class="customer-list-item" style="margin-bottom:10px">
                        <div class="customer-list-main">
                            <strong>{{ ucfirst($past->status) }}</strong>
                            <p>{{ $past->reason }}</p>
                            @if ($past->review_note)<p>Owner&rsquo;s note: {{ $past->review_note }}</p>@endif
                            <small>{{ $past->created_at->format('M d, Y g:i A') }}</small>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
@endsection
