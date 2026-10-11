@extends('layouts.portal')
@section('title', 'System Logs')

@push('head')
    @vite(['resources/js/portal-forms.js'])
@endpush

@section('content')
    @include('partials.settings-header', ['subtitle' => 'Account, booking rules, manager access and activity history.'])

    <section class="card">
        <form method="GET" class="toolbar" role="search">
            <div class="search-box">
                <i class="fas fa-search" aria-hidden="true"></i>
                <input class="form-control" type="search" name="q" value="{{ request('q') }}" placeholder="Search activity" aria-label="Search activity" data-auto-search>
            </div>
            <select class="form-control" name="type" aria-label="Account type" data-auto-submit>
                <option value="">Everyone</option>
                @foreach (['customer' => 'Customers', 'staff' => 'Staff', 'manager' => 'Managers', 'owner' => 'Owners'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <input class="form-control" type="date" name="date" value="{{ request('date') }}" aria-label="Date" data-auto-submit>
            @if (array_filter(request()->only(['q', 'type', 'date'])))
                <a class="btn-ghost btn-sm" href="{{ route('owner.logs') }}">Clear</a>
            @endif
        </form>
        <div class="table-scroll">
            <table class="list-table">
                <thead><tr><th>When</th><th>Who</th><th>Activity</th><th>Details</th></tr></thead>
                <tbody>
                    @forelse ($logs as $log)
                        @php
                            $name = $log->user_type === 'customer' ? ($customerNames[$log->userID] ?? null) : ($staffNames[$log->userID] ?? null);
                        @endphp
                        <tr>
                            <td class="nowrap"><span class="cell-main" style="font-weight:500">{{ $log->created_at?->format('M d, Y') }}</span><span class="cell-sub">{{ $log->created_at?->format('g:i A') }}</span></td>
                            <td><span class="cell-main" style="font-weight:500">{{ $name ?? 'Account #' . $log->userID }}</span><span class="cell-sub">{{ ucfirst($log->user_type) }}</span></td>
                            <td class="nowrap">{{ ucfirst(str_replace('_', ' ', $log->action)) }}</td>
                            <td>{{ $log->details }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="empty-state"><i class="fas fa-list"></i>No activity matches these filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $logs->links() }}
    </section>
@endsection
