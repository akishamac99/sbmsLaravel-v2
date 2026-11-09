@extends('layouts.portal')
@section('title', 'Delete Service')
@push('head') @include('partials.a11y-styles') @endpush

@section('content')
    <div class="page-header"><h1 id="confirm-title">Delete service</h1></div>
    <section class="card confirm-panel" aria-labelledby="confirm-title"><div class="card-body">
        <p>Delete <strong>{{ $service->serviceName }}</strong>? It will be set inactive, so customers can no longer book it and it is removed from their carts. Past bookings keep their link to it.</p>
        @if ($upcoming > 0)
            <p class="alert-inline" role="alert">{{ $upcoming }} upcoming booking(s) already use this service. They are not cancelled; handle them from the Bookings page.</p>
        @endif
        <form method="POST" action="{{ route($role.'.services.destroy', $service) }}">
            @csrf @method('DELETE')
            <div class="form-actions">
                <a class="btn-ghost" href="{{ route($role.'.services') }}" autofocus>Cancel</a>
                <button type="submit" class="btn-primary">Delete service</button>
            </div>
        </form>
    </div></section>
@endsection
