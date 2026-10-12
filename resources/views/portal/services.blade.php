@extends('layouts.portal')
@section('title', 'Services')

@push('head')
    @vite(['resources/js/portal-forms.js'])
@endpush

@section('content')
    @php
        $isOwner = $role === 'owner';
        $currentCategory = (string) request('category', '');
        $keep = array_filter(request()->only(['q', 'status', 'home']));
    @endphp

    <div class="page-header">
        <div class="page-title">
            <h1>Services</h1>
            <p>{{ $isOwner ? 'Your service menu, prices and durations.' : 'The service menu, prices and durations.' }}</p>
        </div>
        @if ($canCreate)
            <div class="page-actions">
                <button type="button" class="btn-primary" data-open-form="serviceModal"><i class="fas fa-plus"></i> Add service</button>
            </div>
        @endif
    </div>

    <section class="card">
        <div class="toolbar">
            <nav class="seg" aria-label="Category">
                <a href="{{ route($role . '.services', $keep) }}" class="{{ $currentCategory === '' ? 'active' : '' }}">All</a>
                @foreach ($categories as $category)
                    <a href="{{ route($role . '.services', $keep + ['category' => $category->catID]) }}" class="{{ $currentCategory === (string) $category->catID ? 'active' : '' }}">{{ $category->category_name }}</a>
                @endforeach
            </nav>
        </div>
        <form method="GET" class="toolbar" role="search">
            @if ($currentCategory !== '')
                <input type="hidden" name="category" value="{{ $currentCategory }}">
            @endif
            <div class="search-box">
                <i class="fas fa-search" aria-hidden="true"></i>
                <input class="form-control" type="search" name="q" value="{{ request('q') }}" placeholder="Search services" aria-label="Search services" data-auto-search>
            </div>
            <select class="form-control" name="status" aria-label="Status" data-auto-submit>
                <option value="">Active and inactive</option>
                <option value="active" @selected(request('status') === 'active')>Active only</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inactive only</option>
            </select>
            <label class="check-row"><input type="checkbox" name="home" value="1" @checked(request('home')) data-auto-submit> Home service</label>
            @if ($keep || $currentCategory !== '')
                <a class="btn-ghost btn-sm" href="{{ route($role . '.services') }}">Clear</a>
            @endif
        </form>

        <div class="table-scroll">
            <table class="list-table">
                <thead><tr><th>Service</th><th class="num">Price</th><th>Duration</th><th>Home service</th><th>Status</th>@if ($canEdit)<th class="col-actions"><span class="sr-only">Actions</span></th>@endif</tr></thead>
                <tbody>
                    @forelse ($services as $service)
                        <tr class="{{ $service->is_active ? '' : 'is-inactive' }}">
                            <td>
                                <div class="cell-thumb">
                                    @if ($service->image)
                                        <img src="{{ $service->image_url }}" alt="" loading="lazy">
                                    @else
                                        <span class="thumb-ph"><i class="fas fa-spa"></i></span>
                                    @endif
                                    <div>
                                        <span class="cell-main">{{ $service->serviceName }}</span>
                                        <span class="cell-sub">{{ $service->category?->category_name ?? 'Uncategorized' }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="num">&#8369;{{ number_format((float) $service->servicePrice, 2) }}</td>
                            <td class="nowrap">{{ $service->duration_label }}</td>
                            <td>{{ $service->is_home_service ? 'Available' : '—' }}</td>
                            <td><span class="pill {{ $service->is_active ? 'p-conf' : 'p-cancel' }}">{{ $service->is_active ? 'Active' : 'Inactive' }}</span></td>
                            @if ($canEdit)
                                <td class="col-actions">
                                    <div class="row-menu">
                                        <button type="button" class="btn-menu" onclick="toggleMenu(this)" aria-label="Actions for {{ $service->serviceName }}"><i class="fas fa-ellipsis-v"></i></button>
                                        <div class="menu-dropdown">
                                            <button type="button" data-open-form="serviceModal"
                                                    data-action="{{ route($role . '.services.update', $service) }}"
                                                    data-record="{{ json_encode([
                                                        'serviceName' => $service->serviceName,
                                                        'catID' => $service->catID,
                                                        'servicePrice' => (float) $service->servicePrice,
                                                        'duration_minutes' => $service->duration_minutes,
                                                        'is_home_service' => (int) $service->is_home_service,
                                                        'description' => $service->description,
                                                        'image_url' => $service->image ? $service->image_url : '',
                                                    ]) }}">
                                                <i class="fas fa-pen"></i> Edit
                                            </button>
                                            <form method="POST" action="{{ route($role . '.services.toggle', $service) }}">
                                                @csrf
                                                <button type="submit" class="{{ $service->is_active ? 'menu-danger' : 'menu-success' }}">
                                                    <i class="fas {{ $service->is_active ? 'fa-eye-slash' : 'fa-eye' }}"></i> {{ $service->is_active ? 'Deactivate' : 'Activate' }}
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty-state"><i class="fas fa-scissors"></i>No services match these filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $services->links() }}
    </section>

    @if ($canCreate || $canEdit)
        <div class="modal" id="serviceModal" role="dialog" aria-modal="true" aria-labelledby="serviceModalTitle" data-modal-form
             @if ($errors->any() && old('_modal') === 'serviceModal') data-reopen @endif>
            <div class="modal-content modal-lg">
                <form method="POST" action="{{ route($role . '.services.create') }}" enctype="multipart/form-data"
                      data-create-action="{{ route($role . '.services.create') }}">
                    @csrf
                    <input type="hidden" name="_method" value="POST">
                    <input type="hidden" name="_modal" value="serviceModal">
                    <input type="hidden" name="_edit_action" value="{{ old('_edit_action') }}">
                    <div class="modal-header">
                        <h3 id="serviceModalTitle" data-title="Add service" data-title-edit="Edit service">Add service</h3>
                        <button type="button" class="close-modal" aria-label="Close">&times;</button>
                    </div>
                    <div class="modal-body">
                        @if ($errors->any() && old('_modal') === 'serviceModal')
                            <div class="alert-inline" role="alert" data-server-error>{{ $errors->first() }}</div>
                        @endif
                        <div class="fields">
                            <label class="field field-full"><span class="field-label">Service name <span class="req" aria-hidden="true">*</span></span>
                                <input class="form-control" name="serviceName" maxlength="100" required value="{{ old('serviceName') }}">
                            </label>
                            <label class="field">Category
                                <select class="form-control" name="catID">
                                    <option value="">Uncategorized</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->catID }}" @selected((string) old('catID') === (string) $category->catID)>{{ $category->category_name }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="field"><span class="field-label">Price <span class="req" aria-hidden="true">*</span></span>
                                <span class="input-suffix"><input class="form-control" type="number" name="servicePrice" min="0" step="0.01" required value="{{ old('servicePrice') }}"><span>PHP</span></span>
                            </label>
                            <label class="field"><span class="field-label">Duration <span class="req" aria-hidden="true">*</span></span>
                                <span class="input-suffix"><input class="form-control" type="number" name="duration_minutes" min="5" max="720" step="5" required value="{{ old('duration_minutes', 60) }}" data-minutes="durationHint"><span>minutes</span></span>
                                <span class="field-hint" id="durationHint">1 hr</span>
                            </label>
                            <div class="field">Home service
                                <label class="check-row" style="min-height:40px"><input type="hidden" name="is_home_service" value="0"><input type="checkbox" name="is_home_service" value="1" @checked(old('is_home_service'))> Can be done at the customer's home</label>
                            </div>
                            <div class="field field-full">Photo
                                <div class="image-field">
                                    <img id="servicePreview" class="preview" alt="" data-image-preview="image_url" hidden>
                                    <span class="preview"><i class="fas fa-image"></i></span>
                                    <div style="display:grid;gap:4px">
                                        <input type="file" name="image" accept="image/jpeg,image/png,image/webp" data-preview-target="servicePreview" aria-label="Service photo">
                                        <span class="field-hint">JPG, PNG or WEBP, up to 5 MB. Leave empty to keep the current photo.</span>
                                        <label class="check-row" data-only-edit hidden><input type="checkbox" name="remove_image" value="1"> Remove current photo</label>
                                    </div>
                                </div>
                            </div>
                            <label class="field field-full">Description
                                <textarea class="form-control" name="description" rows="3" maxlength="1000">{{ old('description') }}</textarea>
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-ghost" onclick="closeModal('serviceModal')">Cancel</button>
                        <button type="submit" class="btn-primary">Save service</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection
