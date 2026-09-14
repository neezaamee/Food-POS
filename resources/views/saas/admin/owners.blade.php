@extends('layouts.app')

@section('title', 'Business Owners Directory - SaaS Admin')

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Header -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('saas.dashboard') }}">SaaS Admin</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Business Owners</li>
                </ol>
            </nav>
            <h1 class="h3 fw-bold text-dark mb-0">Platform Business Owners</h1>
            <p class="text-muted small mb-0">Directory of registered business owners, assigned food points, and ownership records.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('saas.tenants.index') }}" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2">
                <i class="ph-duotone ph-buildings"></i>
                <span>Businesses</span>
            </a>
            <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#createOwnerModal">
                <i class="ph-duotone ph-user-plus"></i>
                <span>Add Business Owner</span>
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
            <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i>Please check the errors below:</div>
            <ul class="mb-0 ps-3 small">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm rounded-4 p-3 mb-4">
        <form action="{{ route('saas.owners.index') }}" method="GET" class="row g-3 align-items-center">
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text bg-light border-0"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control bg-light border-0" placeholder="Search by owner name, email, or phone..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select bg-light border-0" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary px-4 w-100">Filter</button>
                <a href="{{ route('saas.owners.index') }}" class="btn btn-light">Reset</a>
            </div>
        </form>
    </div>

    <!-- Owners Table -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted small text-uppercase">
                    <tr>
                        <th class="ps-4">Owner Name & Email</th>
                        <th>Phone</th>
                        <th>Owned Business(es)</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($owners as $owner)
                    @php
                        $ownedBusiness = $owner->ownedTenants->first() ?? $owner->tenant;
                    @endphp
                    <tr>
                        <td class="ps-4">
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-circle bg-success bg-opacity-10 text-success fw-bold d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; flex-shrink: 0;">
                                    {{ strtoupper(substr($owner->name, 0, 2)) }}
                                </div>
                                <div>
                                    <span class="fw-bold text-dark d-block">{{ $owner->name }}</span>
                                    <span class="text-muted small">{{ $owner->email }}</span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="text-dark">{{ $owner->phone ?? '—' }}</span>
                        </td>
                        <td>
                            @if($ownedBusiness)
                                <a href="{{ route('saas.tenants.show', $ownedBusiness) }}" class="badge bg-light text-dark border text-decoration-none py-1 px-2 d-inline-flex align-items-center gap-1">
                                    <i class="bi bi-shop text-primary"></i>
                                    <span>{{ $ownedBusiness->name }}</span>
                                </a>
                                @if($owner->ownedTenants->count() > 1)
                                    <span class="badge bg-secondary small">+{{ $owner->ownedTenants->count() - 1 }} more</span>
                                @endif
                            @else
                                <span class="text-muted small fst-italic">Unassigned</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge badge-soft-info px-2 py-1 text-uppercase small">Owner</span>
                        </td>
                        <td>
                            @if($owner->status === 'active')
                                <span class="badge badge-soft-success px-2 py-1">Active</span>
                            @else
                                <span class="badge bg-secondary px-2 py-1">{{ ucfirst($owner->status) }}</span>
                            @endif
                        </td>
                        <td class="text-muted small">
                            {{ $owner->created_at->format('M d, Y') }}
                        </td>
                        <td class="text-end pe-4">
                            @if($ownedBusiness)
                                <a href="{{ route('saas.tenants.impersonate', $ownedBusiness) }}" class="btn btn-sm btn-outline-warning d-inline-flex align-items-center gap-1" title="Login as this owner's business">
                                    <i class="ph-duotone ph-user-switch"></i>
                                    <span>Login as Store</span>
                                </a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="ph-duotone ph-users-three fs-1 d-block mb-2 text-secondary"></i>
                            No business owners found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($owners->hasPages())
            <div class="p-3 border-top d-flex justify-content-end">
                {{ $owners->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Modal: Add Owner to Existing Business -->
<div class="modal fade" id="createOwnerModal" tabindex="-1" aria-labelledby="createOwnerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form action="{{ route('saas.owners.store') }}" method="POST">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" id="createOwnerModalLabel">
                        <i class="ph-duotone ph-user-plus text-primary me-2"></i>Add Business Owner
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-3">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Assign to Business <span class="text-danger">*</span></label>
                        <select name="tenant_id" class="form-select" required>
                            <option value="">-- Choose Business --</option>
                            @foreach($allTenants as $t)
                                <option value="{{ $t->id }}">{{ $t->name }} ({{ $t->slug }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Owner Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Asad Khan" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Email Address (Login ID) <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" placeholder="owner@example.com" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Phone Number</label>
                        <input type="text" name="phone" class="form-control" placeholder="e.g. +92 300 0000000">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" placeholder="Minimum 6 characters" required minlength="6">
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_primary" value="1" id="isPrimaryCheck" checked>
                        <label class="form-check-label small" for="isPrimaryCheck">
                            Set as Primary Owner for this business
                        </label>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Owner</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
