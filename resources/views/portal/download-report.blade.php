@extends('layouts.portal')
@section('title', 'Download Report')
@push('head') @include('partials.a11y-styles') @endpush

@section('content')
    <div class="page-header"><h1 id="dl-title">Download report</h1></div>
    <section class="card confirm-panel" aria-labelledby="dl-title"><div class="card-body">
        <p>The file uses the filters currently applied on the Reports page.</p>
        <form method="GET" action="{{ route($role.'.reports.download') }}">
            @foreach ($filters as $name => $value)
                <input type="hidden" name="{{ $name }}" value="{{ $value }}">
            @endforeach
            <fieldset class="form-group">
                <legend>Choose a file format</legend>
                <label><input type="radio" name="format" value="pdf" required checked> PDF document</label><br>
                <label><input type="radio" name="format" value="xlsx"> Excel workbook (.xlsx)</label>
                @error('format')<p class="field-error" role="alert">{{ $message }}</p>@enderror
            </fieldset>
            <div class="form-actions">
                <a class="btn-ghost" href="{{ route($role.'.reports', $filters) }}">Back to report</a>
                <button type="submit" class="btn-primary">Download</button>
            </div>
        </form>
    </div></section>
@endsection
