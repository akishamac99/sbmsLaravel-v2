@extends('layouts.portal')
@section('title', 'Salon Hours')

@section('content')
    @include('partials.settings-header', ['subtitle' => 'When customers can book appointments.'])

    <section class="card">
        <div class="card-header"><span>Opening hours <span class="card-sub">Customers can only pick times inside these hours, and only when a qualified staff member is free. Untick a day to close the salon that day.</span></span></div>
        <form method="POST" action="{{ route('owner.salon-hours.update') }}">
            @csrf
            @method('PUT')
            @if ($errors->any())
                <div class="alert-inline" role="alert" style="margin:16px 20px 0">{{ $errors->first() }}</div>
            @endif
            @include('partials.schedule-editor', ['days' => $days, 'schedule' => $schedule])
            <p class="muted-text" style="padding:0 20px">Appointments start at the opening time and every 30 minutes after it. The last bookable time is 30 minutes before closing.</p>
            <div class="form-actions" style="padding:0 20px 20px">
                <button class="btn-primary" type="submit">Save hours</button>
            </div>
        </form>
    </section>
@endsection
