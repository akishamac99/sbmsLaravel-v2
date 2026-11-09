@extends('layouts.portal')
@section('title', 'Service Categories')
@push('head') @include('partials.a11y-styles') @endpush

@section('content')
    <div class="page-header"><h1>Service categories</h1><p>Group services so customers can find them.</p></div>
    @if ($errors->any())
        <div class="alert-inline" role="alert"><p>Please fix the following:</p><ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <section class="card" aria-labelledby="add-cat-h"><div class="card-header"><span id="add-cat-h">Add category</span></div><div class="card-body">
        <form method="POST" action="{{ route($role.'.categories.store') }}" class="form-grid">
            @csrf
            <div class="form-group"><label for="new-name">Category name</label>
                <input id="new-name" class="form-control" name="category_name" maxlength="100" required value="{{ old('category_name') }}"></div>
            <div class="form-group"><label for="new-desc">Description (optional)</label>
                <input id="new-desc" class="form-control" name="description" maxlength="1000" value="{{ old('description') }}"></div>
            <div class="form-group"><label><input type="checkbox" name="is_event_type" value="1"> This category is for events</label></div>
            <div class="form-actions"><button class="btn-primary" type="submit">Add category</button></div>
        </form>
    </div></section>

    <section class="card" aria-labelledby="cat-list-h"><div class="card-header"><span id="cat-list-h">Categories</span></div><div class="card-body owner-table-wrap">
        <table class="table">
            <caption class="sr-only">Service categories, with the number of services in each</caption>
            <thead><tr><th scope="col">Name</th><th scope="col">Description</th><th scope="col">Services</th><th scope="col">Actions</th></tr></thead>
            <tbody>
            @forelse ($categories as $category)
                <tr>
                    <td colspan="3">
                        <form method="POST" action="{{ route($role.'.categories.update', $category) }}" class="form-grid">
                            @csrf @method('PUT')
                            <label class="sr-only" for="n{{ $category->catID }}">Name of category {{ $category->category_name }}</label>
                            <input id="n{{ $category->catID }}" class="form-control" name="category_name" maxlength="100" required value="{{ $category->category_name }}">
                            <label class="sr-only" for="d{{ $category->catID }}">Description of {{ $category->category_name }}</label>
                            <input id="d{{ $category->catID }}" class="form-control" name="description" maxlength="1000" value="{{ $category->description }}">
                            <span>{{ $category->services_count }} service(s)</span>
                            <label><input type="checkbox" name="is_event_type" value="1" @checked($category->is_event_type)> Event</label>
                            <button class="btn-outline btn-sm" type="submit">Save<span class="sr-only"> {{ $category->category_name }}</span></button>
                        </form>
                    </td>
                    <td>
                        <form method="POST" action="{{ route($role.'.categories.destroy', $category) }}">
                            @csrf @method('DELETE')
                            <button class="btn-ghost btn-sm" type="submit" @disabled($category->services_count > 0)
                                    @if($category->services_count > 0) aria-describedby="cat-lock-{{ $category->catID }}" @endif>Delete<span class="sr-only"> {{ $category->category_name }}</span></button>
                            @if ($category->services_count > 0)<span id="cat-lock-{{ $category->catID }}" class="sr-only">Cannot delete: this category still has services.</span>@endif
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4">No categories yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div></section>
@endsection
