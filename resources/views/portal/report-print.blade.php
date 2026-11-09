@extends('layouts.portal')
@section('title', 'Print Report')
@push('head')
    @include('partials.a11y-styles')
    <style>
        @media print {
            nav, aside, header, .no-print, .toast-stack { display: none !important; }
            .main-content { margin: 0 !important; padding: 0 !important; }
            table { border-collapse: collapse; width: 100%; } th, td { border: 1px solid #666; padding: 4px 6px; }
        }
    </style>
@endpush

@section('content')
    <div class="no-print form-actions">
        <a class="btn-ghost" href="{{ route($role.'.reports', $filters) }}">Back to report</a>
        <button type="button" class="btn-primary" id="print-button">Print report</button>
    </div>
    <article class="card" aria-label="Printable report"><div class="card-body">
        @include('portal.partials.report-body')
    </div></article>
    <script>document.getElementById('print-button').addEventListener('click', function () { window.print(); });</script>
@endsection
