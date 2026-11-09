@extends('layouts.portal')
@section('title', 'Restore Account')
@push('head') @include('partials.a11y-styles') @endpush

@section('content')
    <div class="page-header"><h1 id="confirm-title">Restore account</h1></div>
    <section class="card confirm-panel" aria-labelledby="confirm-title"><div class="card-body">
        <p>Restore the {{ $staff->role }} account of <strong>{{ $staff->full_name }}</strong> ({{ $staff->username }})?
           The account becomes active again and the person can log in. All previous data stays intact.</p>
        <form method="POST" action="{{ route('owner.accounts.restore', $staff) }}">
            @csrf
            <div class="form-actions">
                <a class="btn-ghost" href="{{ route('owner.accounts.archived') }}" autofocus>Cancel</a>
                <button type="submit" class="btn-primary">Restore account</button>
            </div>
        </form>
    </div></section>
@endsection
