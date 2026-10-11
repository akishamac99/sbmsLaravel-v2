@extends('layouts.portal')
@section('title', 'Promos')

@push('head')
    @vite(['resources/js/portal-forms.js'])
@endpush

@section('content')
    <div class="page-header">
        <div class="page-title">
            <h1>Promos</h1>
            <p>Discount codes customers can enter at checkout.</p>
        </div>
        <div class="page-actions">
            <button type="button" class="btn-primary" data-open-form="promoModal"><i class="fas fa-plus"></i> New promo</button>
        </div>
    </div>

    <section class="card">
        <form method="GET" class="toolbar" role="search">
            <div class="search-box">
                <i class="fas fa-search" aria-hidden="true"></i>
                <input class="form-control" type="search" name="q" value="{{ request('q') }}" placeholder="Search code or name" aria-label="Search promos" data-auto-search>
            </div>
            <select class="form-control" name="status" aria-label="Status" data-auto-submit>
                <option value="">Any status</option>
                <option value="running" @selected(request('status') === 'running')>Running now</option>
                <option value="scheduled" @selected(request('status') === 'scheduled')>Scheduled</option>
                <option value="expired" @selected(request('status') === 'expired')>Expired</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Turned off</option>
            </select>
            @if (array_filter(request()->only(['q', 'status'])))
                <a class="btn-ghost btn-sm" href="{{ route('owner.promos') }}">Clear</a>
            @endif
        </form>

        <div class="table-scroll">
            <table class="list-table">
                <thead><tr><th>Code</th><th>Discount</th><th>Valid</th><th class="num">Used</th><th>Status</th><th class="col-actions"><span class="sr-only">Actions</span></th></tr></thead>
                <tbody>
                    @forelse ($promos as $promo)
                        @php
                            $state = ! $promo->is_active ? ['Off', 'p-cancel']
                                : ($promo->valid_until->lt(today()) ? ['Expired', 'p-cancel']
                                : ($promo->valid_from->gt(today()) ? ['Scheduled', 'p-pend'] : ['Running', 'p-conf']));
                            $discount = $promo->discount_type === 'percentage'
                                ? rtrim(rtrim(number_format((float) $promo->discount_value, 2), '0'), '.') . '% off'
                                : '₱' . number_format((float) $promo->discount_value, 2) . ' off';
                        @endphp
                        <tr class="{{ $promo->is_active ? '' : 'is-inactive' }}">
                            <td><span class="cell-main" style="font-family:monospace;letter-spacing:.04em">{{ $promo->promo_code }}</span><span class="cell-sub">{{ $promo->promo_name }}</span></td>
                            <td>
                                <span class="cell-main" style="font-weight:500">{{ $discount }}</span>
                                <span class="cell-sub">
                                    {{ (float) $promo->min_spend > 0 ? 'Min. spend ₱' . number_format((float) $promo->min_spend, 2) : 'No minimum' }}@if ($promo->discount_type === 'percentage' && $promo->max_discount) &middot; up to ₱{{ number_format((float) $promo->max_discount, 2) }}@endif
                                </span>
                            </td>
                            <td class="nowrap">{{ $promo->valid_from->format('M d') }} – {{ $promo->valid_until->format('M d, Y') }}</td>
                            <td class="num">{{ $promo->used_count }} / {{ $promo->usage_limit ?? '∞' }}</td>
                            <td><span class="pill {{ $state[1] }}">{{ $state[0] }}</span></td>
                            <td class="col-actions">
                                <div class="row-menu">
                                    <button type="button" class="btn-menu" onclick="toggleMenu(this)" aria-label="Actions for {{ $promo->promo_code }}"><i class="fas fa-ellipsis-v"></i></button>
                                    <div class="menu-dropdown">
                                        <button type="button" data-open-form="promoModal" data-action="{{ route('owner.promos.update', $promo) }}"
                                                data-record="{{ json_encode([
                                                    'promo_code' => $promo->promo_code,
                                                    'promo_name' => $promo->promo_name,
                                                    'discount_type' => $promo->discount_type,
                                                    'discount_value' => (float) $promo->discount_value,
                                                    'min_spend' => (float) $promo->min_spend,
                                                    'max_discount' => $promo->max_discount !== null ? (float) $promo->max_discount : '',
                                                    'valid_from' => $promo->valid_from->toDateString(),
                                                    'valid_until' => $promo->valid_until->toDateString(),
                                                    'usage_limit' => $promo->usage_limit,
                                                ]) }}">
                                            <i class="fas fa-pen"></i> Edit
                                        </button>
                                        <form method="POST" action="{{ route('owner.promos.toggle', $promo) }}">
                                            @csrf
                                            <button type="submit"><i class="fas {{ $promo->is_active ? 'fa-pause' : 'fa-play' }}"></i> {{ $promo->is_active ? 'Turn off' : 'Turn on' }}</button>
                                        </form>
                                        <form method="POST" action="{{ route('owner.promos.delete', $promo) }}" onsubmit="return confirm('Delete promo {{ $promo->promo_code }}? This cannot be undone.')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="menu-danger"><i class="fas fa-trash"></i> Delete</button>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty-state"><i class="fas fa-tags"></i>No promos match these filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $promos->links() }}
    </section>

    <div class="modal" id="promoModal" role="dialog" aria-modal="true" aria-labelledby="promoModalTitle" data-modal-form
         @if ($errors->any() && old('_modal') === 'promoModal') data-reopen @endif>
        <div class="modal-content modal-lg">
            <form method="POST" action="{{ route('owner.promos.create') }}" data-create-action="{{ route('owner.promos.create') }}">
                @csrf
                <input type="hidden" name="_method" value="POST">
                <input type="hidden" name="_modal" value="promoModal">
                <input type="hidden" name="_edit_action" value="{{ old('_edit_action') }}">
                <div class="modal-header">
                    <h3 id="promoModalTitle" data-title="New promo" data-title-edit="Edit promo">New promo</h3>
                    <button type="button" class="close-modal" aria-label="Close">&times;</button>
                </div>
                <div class="modal-body">
                    @if ($errors->any() && old('_modal') === 'promoModal')
                        <div class="alert-inline" role="alert" data-server-error>{{ $errors->first() }}</div>
                    @endif
                    <div class="fields">
                        <label class="field"><span class="field-label">Code <span class="req" aria-hidden="true">*</span></span>
                            <input class="form-control" name="promo_code" maxlength="50" required placeholder="e.g. SUMMER20" style="text-transform:uppercase" value="{{ old('promo_code') }}">
                            <span class="field-hint">What customers type at checkout. Letters, numbers, - and _.</span>
                        </label>
                        <label class="field"><span class="field-label">Name <span class="req" aria-hidden="true">*</span></span>
                            <input class="form-control" name="promo_name" maxlength="100" required placeholder="e.g. Summer glow sale" value="{{ old('promo_name') }}">
                        </label>
                        <label class="field"><span class="field-label">Discount type <span class="req" aria-hidden="true">*</span></span>
                            <select class="form-control" name="discount_type" data-discount-type>
                                <option value="percentage" @selected(old('discount_type', 'percentage') === 'percentage')>Percentage (%)</option>
                                <option value="fixed" @selected(old('discount_type') === 'fixed')>Fixed amount (PHP)</option>
                            </select>
                        </label>
                        <label class="field"><span class="field-label">Discount <span class="req" aria-hidden="true">*</span></span>
                            <input class="form-control" type="number" min="0.01" step="0.01" name="discount_value" required value="{{ old('discount_value') }}">
                        </label>
                        <label class="field">Minimum spend
                            <span class="input-suffix"><input class="form-control" type="number" min="0" step="0.01" name="min_spend" value="{{ old('min_spend', 0) }}"><span>PHP</span></span>
                            <span class="field-hint">The bill must reach this amount for the code to work. 0 = no minimum.</span>
                        </label>
                        <label class="field" data-cap-field>Discount cap
                            <span class="input-suffix"><input class="form-control" type="number" min="0.01" step="0.01" name="max_discount" value="{{ old('max_discount') }}" placeholder="No cap"><span>PHP</span></span>
                            <span class="field-hint">Most a percentage code can take off. E.g. 20% with a ₱500 cap gives ₱500 off a ₱4,000 bill, not ₱800.</span>
                        </label>
                        <label class="field"><span class="field-label">Starts <span class="req" aria-hidden="true">*</span></span>
                            <input class="form-control" type="date" name="valid_from" required value="{{ old('valid_from', today()->toDateString()) }}">
                        </label>
                        <label class="field"><span class="field-label">Ends <span class="req" aria-hidden="true">*</span></span>
                            <input class="form-control" type="date" name="valid_until" required value="{{ old('valid_until', today()->addMonth()->toDateString()) }}">
                        </label>
                        <label class="field">Usage limit
                            <input class="form-control" type="number" min="1" name="usage_limit" value="{{ old('usage_limit') }}" placeholder="Unlimited">
                            <span class="field-hint">Total times the code can be used, by all customers.</span>
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-ghost" onclick="closeModal('promoModal')">Cancel</button>
                    <button type="submit" class="btn-primary">Save promo</button>
                </div>
            </form>
        </div>
    </div>
    <script>
        (function () {
            var type = document.querySelector('[data-discount-type]');
            var cap = document.querySelector('[data-cap-field]');
            function sync() { cap.hidden = type.value !== 'percentage'; }
            type.addEventListener('change', sync);
            type.addEventListener('input', sync);
            sync();
        })();
    </script>
@endsection
