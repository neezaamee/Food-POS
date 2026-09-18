@extends('layouts.app')

@section('title', 'Roles & Permissions Management')

@section('content')
<div class="row g-4 mb-4">
    <div class="col-12 d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <h4 class="mb-1 fw-bold text-body-emphasis">Access Control & Permissions</h4>
            <p class="text-muted mb-0 small">Manage staff roles, operational boundaries, and security permissions</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('users.index') }}" class="btn btn-outline-secondary d-flex align-items-center gap-2">
                <i class="ph-duotone ph-users"></i> Staff Users
            </a>
            <a href="{{ route('roles.create') }}" class="btn btn-primary d-flex align-items-center gap-2">
                <i class="ph-duotone ph-plus-circle"></i> Create Custom Role
            </a>
        </div>
    </div>
</div>

<!-- Navigation Tabs -->
<ul class="nav nav-tabs border-bottom mb-4">
    <li class="nav-item">
        <a class="nav-link text-muted" href="{{ route('users.index') }}">
            <i class="ph-duotone ph-users me-1"></i> Staff Accounts
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link active fw-bold border-bottom-0" href="{{ route('roles.index') }}">
            <i class="ph-duotone ph-shield-check me-1 text-primary"></i> Roles & Permissions Matrix
        </a>
    </li>
</ul>

<!-- Metrics Overview -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 p-3 bg-primary bg-opacity-10 text-primary fs-3">
                    <i class="ph-duotone ph-shield-star"></i>
                </div>
                <div>
                    <div class="text-muted small">Total Roles</div>
                    <h5 class="fw-bold mb-0">{{ $roles->total() }}</h5>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 p-3 bg-info bg-opacity-10 text-info fs-3">
                    <i class="ph-duotone ph-lock-key"></i>
                </div>
                <div>
                    <div class="text-muted small">System Pre-Set</div>
                    <h5 class="fw-bold mb-0">{{ $roles->where('is_system', true)->count() }}</h5>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 p-3 bg-success bg-opacity-10 text-success fs-3">
                    <i class="ph-duotone ph-user-gear"></i>
                </div>
                <div>
                    <div class="text-muted small">Custom Roles</div>
                    <h5 class="fw-bold mb-0">{{ $roles->where('is_system', false)->count() }}</h5>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 p-3 bg-warning bg-opacity-10 text-warning fs-3">
                    <i class="ph-duotone ph-check-square-offset"></i>
                </div>
                <div>
                    <div class="text-muted small">Total Privileges</div>
                    <h5 class="fw-bold mb-0">{{ $totalPermissions }} Actions</h5>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Roles Table Card -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-header bg-transparent border-0 pt-3 px-3 pb-2 d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <h6 class="fw-bold mb-0">Configured Roles ({{ $roles->total() }})</h6>
        <form method="GET" action="{{ route('roles.index') }}" class="d-flex gap-2">
            <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Search role name or slug..." style="max-width: 250px;">
            <button type="submit" class="btn btn-outline-secondary btn-sm">Search</button>
            @if(request('search'))
                <a href="{{ route('roles.index') }}" class="btn btn-light btn-sm">Reset</a>
            @endif
        </form>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Role Details</th>
                        <th>Type</th>
                        <th>Assigned Staff</th>
                        <th>Permission Scope</th>
                        <th class="text-end pe-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($roles as $role)
                    <tr>
                        <td class="ps-3 py-3">
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar-sm rounded-circle {{ $role->isSystem() ? 'bg-secondary bg-opacity-10 text-secondary' : 'bg-primary bg-opacity-10 text-primary' }} d-flex align-items-center justify-content-center fw-bold" style="width: 38px; height: 38px;">
                                    <i class="ph-duotone {{ $role->isSystem() ? 'ph-shield' : 'ph-user-gear' }} fs-5"></i>
                                </div>
                                <div>
                                    <div class="fw-bold text-body">{{ $role->name }}</div>
                                    <div class="text-muted small font-monospace">slug: {{ $role->slug }}</div>
                                    @if($role->description)
                                        <div class="text-muted small mt-1" style="max-width: 380px;">{{ $role->description }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            @if($role->isSystem())
                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                                    <i class="ph-duotone ph-lock-key me-1"></i> System Default
                                </span>
                            @else
                                <span class="badge bg-success-subtle text-success border border-success-subtle">
                                    <i class="ph-duotone ph-sparkle me-1"></i> Custom Role
                                </span>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border px-2 py-1">
                                <i class="ph-duotone ph-users me-1 text-primary"></i> {{ $role->users_count }} user(s)
                            </span>
                        </td>
                        <td>
                            @php
                                $permCount = $role->permissions->count();
                                $pct = $totalPermissions > 0 ? round(($permCount / $totalPermissions) * 100) : 0;
                            @endphp
                            <div style="min-width: 160px; max-width: 220px;">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="small fw-semibold">{{ $permCount }} / {{ $totalPermissions }}</span>
                                    <span class="small text-muted">{{ $pct }}%</span>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar {{ $pct > 75 ? 'bg-success' : ($pct > 30 ? 'bg-primary' : 'bg-info') }}" role="progressbar" style="width: {{ $pct }}%"></div>
                                </div>
                            </div>
                        </td>
                        <td class="text-end pe-3">
                            <div class="d-inline-flex gap-1">
                                <a href="{{ route('roles.edit', $role->id) }}" class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="Edit Permissions">
                                    <i class="ph-duotone ph-pencil-simple me-1"></i> Configure
                                </a>

                                @if(! $role->isSystem())
                                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteRoleModal{{ $role->id }}" title="Delete Role">
                                        <i class="ph-duotone ph-trash"></i>
                                    </button>

                                    <!-- Delete Modal -->
                                    <div class="modal fade" id="deleteRoleModal{{ $role->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered text-start">
                                            <div class="modal-content border-0 shadow">
                                                <form method="POST" action="{{ route('roles.destroy', $role->id) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <div class="modal-header border-bottom">
                                                        <h6 class="modal-title fw-bold text-danger">
                                                            <i class="ph-duotone ph-warning me-1"></i> Delete Role: {{ $role->name }}
                                                        </h6>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body p-4">
                                                        <p class="mb-2">Are you sure you want to delete this custom role?</p>
                                                        @if($role->users_count > 0)
                                                            <div class="alert alert-danger py-2 small mb-0">
                                                                <strong>Blocked:</strong> There are currently <strong>{{ $role->users_count }}</strong> staff member(s) assigned to this role. You must reassign those users to another role before this role can be removed.
                                                            </div>
                                                        @else
                                                            <p class="text-muted small mb-0">This action cannot be undone. Any permissions associated with this custom role will be unlinked.</p>
                                                        @endif
                                                    </div>
                                                    <div class="modal-footer border-top">
                                                        <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-danger btn-sm" {{ $role->users_count > 0 ? 'disabled' : '' }}>
                                                            Delete Role
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">
                            <i class="ph-duotone ph-shield-slash fs-1 d-block mb-2 text-secondary"></i>
                            No roles matching the criteria found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($roles->hasPages())
    <div class="card-footer bg-transparent border-0 px-3 py-2">
        {{ $roles->links() }}
    </div>
    @endif
</div>
@endsection
