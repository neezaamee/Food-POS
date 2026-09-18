@extends('layouts.app')

@section('title', 'Staff & User Management')

@section('content')
<div class="row g-4 mb-4">
    <div class="col-12 d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <h4 class="mb-1 fw-bold text-body-emphasis">Staff & User Management</h4>
            <p class="text-muted mb-0 small">Manage restaurant roles, cashier logins, and permissions</p>
        </div>
        <button class="btn btn-primary d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="bi bi-person-plus-fill"></i> Add New User
        </button>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
    <ul class="mb-0 small">
        @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
        @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<!-- Navigation Tabs -->
<ul class="nav nav-tabs border-bottom mb-4">
    <li class="nav-item">
        <a class="nav-link active fw-bold border-bottom-0" href="{{ route('users.index') }}">
            <i class="ph-duotone ph-users me-1 text-primary"></i> Staff Accounts
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link text-muted" href="{{ route('roles.index') }}">
            <i class="ph-duotone ph-shield-check me-1"></i> Roles & Permissions Matrix
        </a>
    </li>
</ul>

<div class="card border-0 shadow-sm rounded-3">
    <div class="card-header bg-transparent border-0 pt-3 px-3 pb-2 d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <h6 class="fw-bold mb-0">System Users ({{ $users->total() }})</h6>
        <form method="GET" action="{{ route('admin.users') }}" class="d-flex gap-2">
            <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Search user..." style="max-width: 220px;">
            <select name="role" class="form-select form-select-sm" style="max-width: 160px;" onchange="this.form.submit()">
                <option value="">All Roles</option>
                @foreach($roles as $r)
                    <option value="{{ $r->slug }}" {{ request('role') == $r->slug ? 'selected' : '' }}>{{ $r->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-outline-secondary btn-sm">Search</button>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Name & Contact</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Created At</th>
                        <th class="text-end pe-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $u)
                    <tr>
                        <td class="ps-3">
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar-sm rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center fw-bold" style="width: 36px; height: 36px;">
                                    {{ strtoupper(substr($u->name, 0, 1)) }}
                                </div>
                                <div>
                                    <div class="fw-bold text-body">{{ $u->name }}</div>
                                    <div class="text-muted small">{{ $u->email }} {{ $u->phone ? '• ' . $u->phone : '' }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                                {{ ucfirst(str_replace(['-', '_'], ' ', $u->role)) }}
                            </span>
                        </td>
                        <td>
                            @if($u->status === 'active')
                                <span class="badge bg-success-subtle text-success">Active</span>
                            @else
                                <span class="badge bg-danger-subtle text-danger">Inactive</span>
                            @endif
                        </td>
                        <td class="text-muted small">{{ $u->created_at->format('M d, Y') }}</td>
                        <td class="text-end pe-3">
                            <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#editUserModal{{ $u->id }}">
                                <i class="bi bi-pencil-square"></i> Edit
                            </button>

                            <!-- Edit Modal -->
                            <div class="modal fade" id="editUserModal{{ $u->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered text-start">
                                    <div class="modal-content border-0 shadow">
                                        <form method="POST" action="{{ route('admin.users.update', $u->id) }}">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-header border-bottom">
                                                <h6 class="modal-title fw-bold">Edit User: {{ $u->name }}</h6>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body p-4">
                                                <div class="mb-3">
                                                    <label class="form-label small fw-medium">Full Name</label>
                                                    <input type="text" name="name" class="form-control" value="{{ $u->name }}" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label small fw-medium">Email Address</label>
                                                    <input type="email" name="email" class="form-control" value="{{ $u->email }}" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label small fw-medium">Phone</label>
                                                    <input type="text" name="phone" class="form-control" value="{{ $u->phone }}">
                                                </div>
                                                <div class="row g-2 mb-3">
                                                    <div class="col-6">
                                                        <label class="form-label small fw-medium">Role</label>
                                                        <select name="role" class="form-select" required>
                                                            @foreach($roles as $r)
                                                                <option value="{{ $r->slug }}" {{ $u->role == $r->slug ? 'selected' : '' }}>{{ $r->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-6">
                                                        <label class="form-label small fw-medium">Status</label>
                                                        <select name="status" class="form-select" required>
                                                            <option value="active" {{ $u->status == 'active' ? 'selected' : '' }}>Active</option>
                                                            <option value="inactive" {{ $u->status == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="mb-2">
                                                    <label class="form-label small fw-medium">Reset Password (leave empty to retain)</label>
                                                    <input type="password" name="password" class="form-control" placeholder="New password">
                                                </div>
                                            </div>
                                            <div class="modal-footer border-top">
                                                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-primary btn-sm">Save Changes</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">No users found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($users->hasPages())
    <div class="card-footer bg-transparent border-0 px-3 py-2">
        {{ $users->links() }}
    </div>
    @endif
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="{{ route('admin.users.store') }}">
                @csrf
                <div class="modal-header border-bottom">
                    <h6 class="modal-title fw-bold">Create User Account</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-medium">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g. Ali Khan">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-medium">Email Address <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" required placeholder="name@foodpoint.com">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-medium">Phone</label>
                        <input type="text" name="phone" class="form-control" placeholder="+92 300 1234567">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-medium">Role Assignment <span class="text-danger">*</span></label>
                        <select name="role" class="form-select" required>
                            @foreach($roles as $r)
                                <option value="{{ $r->slug }}">{{ $r->name }} ({{ $r->slug }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-medium">Initial Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" required placeholder="Minimum 6 characters">
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">Create User</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
