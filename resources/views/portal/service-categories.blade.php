@extends('layouts.portal')
@section('title', 'Service Categories')

@push('head')
    @include('partials.a11y-styles')
    @vite(['resources/js/portal-forms.js'])
@endpush

@section('content')
    <div class="page-header">
        <div class="page-title">
            <h1>Service Categories</h1>
            <p>Group services so customers can find them. A category with services in it cannot be deleted.</p>
        </div>
        @if ($canEdit)
            <div class="page-actions">
                <button type="button" class="btn-primary" data-open-form="categoryModal"><i class="fas fa-plus" aria-hidden="true"></i> Add category</button>
            </div>
        @endif
    </div>

    @if ($errors->any() && old('_modal') !== 'categoryModal')
        <div class="alert-inline" role="alert"><p>Please fix the following:</p><ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <section class="card">
        <div class="table-scroll">
            <table class="list-table">
                <caption class="sr-only">Service categories and the number of services in each</caption>
                <thead>
                    <tr>
                        <th scope="col">Category</th>
                        <th scope="col">Description</th>
                        <th scope="col">Type</th>
                        <th scope="col" class="num">Services</th>
                        @if ($canEdit)<th scope="col" class="col-actions"><span class="sr-only">Actions</span></th>@endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($categories as $category)
                        <tr>
                            <td><span class="cell-main">{{ $category->category_name }}</span></td>
                            <td>{{ $category->description ?: '—' }}</td>
                            <td>
                                @if ($category->is_event_type)
                                    <span class="pill p-pend">Event</span>
                                @else
                                    <span class="pill p-conf">Regular</span>
                                @endif
                            </td>
                            <td class="num">{{ $category->services_count }}</td>
                            @if ($canEdit)
                                <td class="col-actions">
                                    <div class="row-menu">
                                        <button type="button" class="btn-menu" onclick="toggleMenu(this)" aria-label="Actions for {{ $category->category_name }}"><i class="fas fa-ellipsis-v" aria-hidden="true"></i></button>
                                        <div class="menu-dropdown">
                                            <button type="button" data-open-form="categoryModal"
                                                    data-action="{{ route($role . '.categories.update', $category) }}"
                                                    data-record="{{ json_encode([
                                                        'category_name' => $category->category_name,
                                                        'description' => $category->description,
                                                        'is_event_type' => (int) $category->is_event_type,
                                                    ]) }}">
                                                <i class="fas fa-pen" aria-hidden="true"></i> Edit
                                            </button>
                                            @if ($category->services_count > 0)
                                                <button type="button" disabled title="Move or delete its {{ $category->services_count }} service(s) first">
                                                    <i class="fas fa-trash" aria-hidden="true"></i> Delete (in use)
                                                </button>
                                            @else
                                                <form method="POST" action="{{ route($role . '.categories.destroy', $category) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="menu-danger" onclick="return confirm('Delete the category {{ e($category->category_name) }}?')"><i class="fas fa-trash" aria-hidden="true"></i> Delete</button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="{{ $canEdit ? 5 : 4 }}" class="empty-state"><i class="fas fa-layer-group" aria-hidden="true"></i>No categories yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if ($canEdit)
        <div class="modal" id="categoryModal" role="dialog" aria-modal="true" aria-labelledby="categoryModalTitle" data-modal-form
             @if ($errors->any() && old('_modal') === 'categoryModal') data-reopen @endif>
            <div class="modal-content">
                <form method="POST" action="{{ route($role . '.categories.store') }}" data-create-action="{{ route($role . '.categories.store') }}">
                    @csrf
                    <input type="hidden" name="_method" value="POST">
                    <input type="hidden" name="_modal" value="categoryModal">
                    <input type="hidden" name="_edit_action" value="{{ old('_edit_action') }}">
                    <div class="modal-header">
                        <h3 id="categoryModalTitle" data-title="Add category" data-title-edit="Edit category">Add category</h3>
                        <button type="button" class="close-modal" aria-label="Close">&times;</button>
                    </div>
                    <div class="modal-body">
                        @if ($errors->any() && old('_modal') === 'categoryModal')
                            <div class="alert-inline" role="alert" data-server-error>{{ $errors->first() }}</div>
                        @endif
                        <div class="fields">
                            <label class="field field-full"><span class="field-label">Category name <span class="req" aria-hidden="true">*</span></span>
                                <input class="form-control" name="category_name" maxlength="100" required value="{{ old('category_name') }}">
                            </label>
                            <label class="field field-full">Description <span class="field-hint">Optional</span>
                                <textarea class="form-control" name="description" rows="3" maxlength="1000">{{ old('description') }}</textarea>
                            </label>
                            <div class="field field-full">Type
                                <label class="check-row"><input type="hidden" name="is_event_type" value="0"><input type="checkbox" name="is_event_type" value="1" @checked(old('is_event_type'))> This category is for events</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-ghost" onclick="closeModal('categoryModal')">Cancel</button>
                        <button type="submit" class="btn-primary">Save category</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection
