@extends('layouts.portal')
@section('title', 'Staff')

@push('head')
    @vite(['resources/js/portal-forms.js'])
@endpush

@section('content')
    <div class="page-header">
        <div class="page-title">
            <h1>Staff</h1>
            <p>Staff and manager accounts. Archived accounts keep their appointment history.</p>
        </div>
        <div class="page-actions">
            <button type="button" class="btn-primary" data-open-form="staffModal"><i class="fas fa-plus"></i> Add staff</button>
        </div>
    </div>

    <section class="card">
        <form method="GET" class="toolbar" role="search">
            <div class="search-box">
                <i class="fas fa-search" aria-hidden="true"></i>
                <input class="form-control" type="search" name="q" value="{{ request('q') }}" placeholder="Search name, email or specialization" aria-label="Search staff" data-auto-search>
            </div>
            <select class="form-control" name="role" aria-label="Role" data-auto-submit>
                <option value="">All roles</option>
                @foreach (['Owner', 'Manager', 'Staff'] as $roleOption)
                    <option value="{{ $roleOption }}" @selected(request('role') === $roleOption)>{{ $roleOption }}</option>
                @endforeach
            </select>
            <select class="form-control" name="status" aria-label="Status" data-auto-submit>
                <option value="">Any status</option>
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
            </select>
            @if (array_filter(request()->only(['q', 'role', 'status'])))
                <a class="btn-ghost btn-sm" href="{{ route('owner.staff.manage') }}">Clear</a>
            @endif
        </form>

        <div class="table-scroll">
            <table class="list-table">
                <thead><tr><th>Name</th><th>Role</th><th>Specialization</th><th>Contact</th><th>Status</th><th class="col-actions"><span class="sr-only">Actions</span></th></tr></thead>
                <tbody>
                    @forelse ($staffMembers as $staff)
                        <tr class="{{ $staff->is_active ? '' : 'is-inactive' }}">
                            <td><span class="cell-main">{{ $staff->full_name }}</span><span class="cell-sub">{{ $staff->email }} &middot; {{ '@' . $staff->username }}</span></td>
                            <td><span class="tag">{{ $staff->role }}</span></td>
                            <td>{{ $staff->specialization ?: 'General' }}</td>
                            <td>{{ $staff->contactNumber ?: '—' }}</td>
                            <td><span class="pill {{ $staff->is_active ? 'p-conf' : 'p-cancel' }}">{{ $staff->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td class="col-actions">
                                @unless ($staff->isOwner())
                                    <div class="row-menu">
                                        <button type="button" class="btn-menu" onclick="toggleMenu(this)" aria-label="Actions for {{ $staff->full_name }}"><i class="fas fa-ellipsis-v"></i></button>
                                        <div class="menu-dropdown">
                                            <button type="button" data-open-form="staffModal"
                                                    data-action="{{ route('owner.staff.update', $staff) }}"
                                                    data-record="{{ json_encode($staff->only(['firstName', 'lastName', 'username', 'email', 'role', 'specialization', 'contactNumber'])) }}">
                                                <i class="fas fa-pen"></i> Edit
                                            </button>
                                            <a href="{{ route('owner.staff-availability', ['staff_id' => $staff->staffID]) }}"><i class="fas fa-clock"></i> Working hours</a>
                                            <form method="POST" action="{{ route('owner.staff.toggle', $staff) }}">
                                                @csrf
                                                <button type="submit"><i class="fas {{ $staff->is_active ? 'fa-user-slash' : 'fa-user-check' }}"></i> {{ $staff->is_active ? 'Deactivate' : 'Activate' }}</button>
                                            </form>
                                            <form method="POST" action="{{ route('owner.staff.archive', $staff) }}" onsubmit="return confirm('Archive {{ $staff->full_name }}? They will no longer appear in lists or be able to log in. Their history is kept.')">
                                                @csrf
                                                <button type="submit" class="menu-danger"><i class="fas fa-box-archive"></i> Archive</button>
                                            </form>
                                        </div>
                                    </div>
                                @endunless
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty-state"><i class="fas fa-id-card"></i>No staff match these filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $staffMembers->links() }}
    </section>

    <div class="modal" id="staffModal" role="dialog" aria-modal="true" aria-labelledby="staffModalTitle" data-modal-form
         @if ($errors->any() && old('_modal') === 'staffModal') data-reopen @endif>
        <div class="modal-content modal-lg">
            <form method="POST" action="{{ route('owner.staff.create') }}" data-create-action="{{ route('owner.staff.create') }}">
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
                        <label class="field"><span class="field-label">Role <span class="req" aria-hidden="true">*</span></span>
                            <select class="form-control" name="role">
                                <option value="Staff" @selected(old('role') !== 'Manager')>Staff</option>
                                <option value="Manager" @selected(old('role') === 'Manager')>Manager</option>
                            </select>
                        </label>
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
@endsection
