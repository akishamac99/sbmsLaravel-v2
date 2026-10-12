@extends('layouts.portal')
@section('title', 'Staff Directory')

@push('head')
    @vite(['resources/js/portal-forms.js'])
@endpush

@section('content')
    @php $hasMenu = $canEdit || $canDelete; @endphp

    <div class="page-header">
        <div class="page-title">
            <h1>Staff Directory</h1>
            <p>Who is on the team and what they specialise in.</p>
        </div>
        @if ($canCreate)
            <div class="page-actions">
                <button type="button" class="btn-primary" data-open-form="staffModal"><i class="fas fa-plus"></i> Add staff</button>
            </div>
        @endif
    </div>

    <section class="card">
        <div class="table-scroll">
            <table class="list-table">
                <thead><tr><th>Name</th><th>Role</th><th>Specialization</th><th>Contact</th><th>Status</th><th class="num">Bookings</th>@if ($hasMenu)<th class="col-actions"><span class="sr-only">Actions</span></th>@endif</tr></thead>
                <tbody>
                    @forelse ($staffMembers as $staff)
                        <tr class="{{ $staff->is_active ? '' : 'is-inactive' }}">
                            <td><span class="cell-main">{{ $staff->full_name }}</span><span class="cell-sub">{{ $staff->email }}</span></td>
                            <td><span class="tag">{{ $staff->role }}</span></td>
                            <td>{{ $staff->specialization ?: 'General' }}</td>
                            <td>{{ $staff->contactNumber ?: '—' }}</td>
                            <td><span class="pill {{ $staff->is_active ? 'p-conf' : 'p-cancel' }}">{{ $staff->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td class="num">{{ $staff->bookings_count }}</td>
                            @if ($hasMenu)
                                <td class="col-actions">
                                    {{-- Managers can only manage Staff accounts; the owner row is never editable. --}}
                                    @if (! $staff->isOwner() && ($role === 'owner' || $staff->role === 'Staff'))
                                        <div class="row-menu">
                                            <button type="button" class="btn-menu" onclick="toggleMenu(this)" aria-label="Actions for {{ $staff->full_name }}"><i class="fas fa-ellipsis-v"></i></button>
                                            <div class="menu-dropdown">
                                                @if ($canEdit)
                                                    <button type="button" data-open-form="staffModal"
                                                            data-action="{{ route($role . '.staff.update', $staff) }}"
                                                            data-record="{{ json_encode($staff->only(['firstName', 'lastName', 'username', 'email', 'role', 'specialization', 'contactNumber'])) }}">
                                                        <i class="fas fa-pen"></i> Edit
                                                    </button>
                                                    <form method="POST" action="{{ route($role . '.staff.toggle', $staff) }}">
                                                        @csrf
                                                        <button type="submit"><i class="fas {{ $staff->is_active ? 'fa-user-slash' : 'fa-user-check' }}"></i> {{ $staff->is_active ? 'Deactivate' : 'Activate' }}</button>
                                                    </form>
                                                @endif
                                                @if ($canDelete)
                                                    <form method="POST" action="{{ route($role . '.staff.archive', $staff) }}" onsubmit="return confirm('Archive {{ $staff->full_name }}? They will no longer appear in lists or be able to log in. Their history is kept.')">
                                                        @csrf
                                                        <button type="submit" class="menu-danger"><i class="fas fa-box-archive"></i> Archive</button>
                                                    </form>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="7" class="empty-state"><i class="fas fa-id-card"></i>No staff members found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $staffMembers->links() }}
    </section>

    @if ($canCreate || $canEdit)
        <div class="modal" id="staffModal" role="dialog" aria-modal="true" aria-labelledby="staffModalTitle" data-modal-form
             @if ($errors->any() && old('_modal') === 'staffModal') data-reopen @endif>
            <div class="modal-content modal-lg">
                <form method="POST" action="{{ route($role . '.staff.create') }}" data-create-action="{{ route($role . '.staff.create') }}" data-edit-method="PUT">
                    @csrf
                    <input type="hidden" name="_method" value="POST">
                    <input type="hidden" name="_modal" value="staffModal">
                    <input type="hidden" name="_edit_action" value="{{ old('_edit_action') }}">
                    <div class="modal-header">
                        <h3 id="staffModalTitle" data-title="Add staff account" data-title-edit="Edit staff account">Add staff account</h3>
                        <button type="button" class="close-modal" aria-label="Close">&times;</button>
                    </div>
                    <div class="modal-body">
                        @if ($errors->any() && old('_modal') === 'staffModal')
                            <div class="alert-inline" role="alert" data-server-error>{{ $errors->first() }}</div>
                        @endif
                        <div class="fields">
                            <label class="field"><span class="field-label">First name <span class="req" aria-hidden="true">*</span></span>
                                <input class="form-control" name="firstName" required maxlength="50" value="{{ old('firstName') }}">
                            </label>
                            <label class="field"><span class="field-label">Last name <span class="req" aria-hidden="true">*</span></span>
                                <input class="form-control" name="lastName" required maxlength="50" value="{{ old('lastName') }}">
                            </label>
                            <label class="field"><span class="field-label">Email <span class="req" aria-hidden="true">*</span></span>
                                <input class="form-control" type="email" name="email" required maxlength="100" value="{{ old('email') }}">
                            </label>
                            <label class="field"><span class="field-label">Username <span class="req" aria-hidden="true">*</span></span>
                                <input class="form-control" name="username" required maxlength="50" value="{{ old('username') }}">
                            </label>
                            @if ($role === 'owner')
                                <label class="field"><span class="field-label">Role <span class="req" aria-hidden="true">*</span></span>
                                    <select class="form-control" name="role">
                                        <option value="Staff" @selected(old('role') !== 'Manager')>Staff</option>
                                        <option value="Manager" @selected(old('role') === 'Manager')>Manager</option>
                                    </select>
                                </label>
                            @endif
                            <label class="field">Contact number
                                <input class="form-control" name="contactNumber" maxlength="20" value="{{ old('contactNumber') }}">
                            </label>
                            <label class="field field-full">Specialization
                                <input class="form-control" name="specialization" maxlength="100" placeholder="e.g. Hair and Makeup Services" value="{{ old('specialization') }}">
                                <span class="field-hint">Used to match staff to services, so include the service types they do.</span>
                            </label>
                            <label class="field field-full" data-only-create><span class="field-label">Temporary password <span class="req" aria-hidden="true">*</span></span>
                                <input class="form-control" type="password" name="password" required minlength="8" autocomplete="new-password">
                                <span class="field-hint">At least 8 characters. Ask them to change it from My Profile after logging in.</span>
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-ghost" onclick="closeModal('staffModal')">Cancel</button>
                        <button type="submit" class="btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection
