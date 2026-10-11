@extends('layouts.portal')
@section('title', 'My Availability')

@section('content')
    @include('partials.settings-header', ['subtitle' => 'Your account and working hours.'])

    <section class="card">
        <div class="card-header"><span>Weekly working hours <span class="card-sub">Customers can only book you during these hours.</span></span></div>
        <form method="POST" action="{{ route('staff.availability.update') }}">
            @csrf
            @if ($errors->any())
                <div class="alert-inline" role="alert" style="margin:16px 20px 0">{{ $errors->first() }}</div>
            @endif
            @include('partials.schedule-editor', ['days' => $days, 'schedule' => $schedule])
            <div class="form-actions" style="padding:0 20px 20px">
                <button class="btn-primary" type="submit">Save hours</button>
            </div>
        </form>
    </section>
@endsection
