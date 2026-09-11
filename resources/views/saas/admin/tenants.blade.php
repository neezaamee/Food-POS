@extends('layouts.app')

@section('title', 'Food Points / Tenants Directory')

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Header -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('saas.dashboard') }}">SaaS Admin</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Tenants</li>
                </ol>
            </nav>
            <h1 class="h3 fw-bold text-dark mb-0">Food Points & Restaurants</h1>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('saas.dashboard') }}" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2">
                <i class="ph-duotone ph-arrow-left"></i>
                <span>Overview</span>
            </a>
            <a href="{{ route('tenant.register') }}" target="_blank" class="btn btn-primary d-inline-flex align-items-center gap-2">
                <i class="ph-duotone ph-plus-circle"></i>
                <span>Register New Tenant</span>
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm rounded-4 p-3 mb-4">
        <form action="{{ route('saas.tenants.index') }}" method="GET" class="row g-3 align-items-center">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-light border-0"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control bg-light border-0" placeholder="Search by restaurant name, email, slug, phone..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select bg-light border-0" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="trial" {{ request('status') === 'trial' ? 'selected' : '' }}>Trial</option>
                    <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary px-4">Filter</button>
                <a href="{{ route('saas.tenants.index') }}" class="btn btn-light px-3">Reset</a>
            </div>
        </form>
    </div>

    <!-- Tenants Table -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted small text-uppercase">
                    <tr>
                        <th class="ps-4">ID</th>
                        <th>Restaurant / Food Point</th>
                        <th>Subdomain / Slug</th>
                        <th>Current Plan</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tenants as $tenant)
                    @php
                        $sub = $tenant->subscriptions->first();
                    @endphp
                    <tr>
                        <td class="ps-4 fw-semibold text-muted">#{{ $tenant->id }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-circle bg-primary bg-opacity-10 text-primary fw-bold d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                    {{ strtoupper(substr($tenant->name, 0, 2)) }}
                                </div>
                                <div>
                                    <a href="{{ route('saas.tenants.show', $tenant) }}" class="fw-bold text-dark text-decoration-none d-block">
                                        {{ $tenant->name }}
                                    </a>
                                    <span class="text-muted small">{{ $tenant->email ?? 'No email' }} • {{ $tenant->phone ?? 'No phone' }}</span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <code class="text-primary bg-light px-2 py-1 rounded">{{ $tenant->slug }}</code>
                        </td>
                        <td>
                            @if($sub && $sub->plan)
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1">
                                    {{ $sub->plan->name }}
                                </span>
                            @else
                                <span class="badge bg-light text-muted border">No Plan</span>
                            @endif
                        </td>
                        <td>
                            @if($tenant->status === 'active')
                                <span class="badge badge-soft-success">Active</span>
                            @elseif($tenant->status === 'trial')
                                <span class="badge badge-soft-warning">Trial</span>
                            @elseif($tenant->status === 'suspended')
                                <span class="badge badge-soft-danger">Suspended</span>
                            @else
                                <span class="badge bg-secondary">{{ ucfirst($tenant->status) }}</span>
                            @endif
                        </td>
                        <td class="text-muted small">
                            {{ $tenant->created_at->format('M d, Y') }}
                        </td>
                        <td class="text-end pe-4">
                            <div class="d-flex align-items-center justify-content-end gap-1">
                                <!-- Impersonate Button -->
                                <a href="{{ route('saas.tenants.impersonate', $tenant) }}" class="btn btn-sm btn-outline-warning d-inline-flex align-items-center gap-1" title="Login as this tenant">
                                    <i class="ph-duotone ph-user-switch"></i>
                                    <span>Login</span>
                                </a>

                                <!-- View Details -->
                                <a href="{{ route('saas.tenants.show', $tenant) }}" class="btn btn-sm btn-outline-secondary" title="View Full Details">
                                    <i class="bi bi-eye"></i>
                                </a>

                                <!-- Action Dropdown -->
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light border-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3">
                                        <li><h6 class="dropdown-header">Manage Tenant</h6></li>
                                        <li>
                                            <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#statusModal{{ $tenant->id }}">
                                                <i class="bi bi-toggles me-2 text-primary"></i>Change Status
                                            </button>
                                        </li>
                                        <li>
                                            <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#planModal{{ $tenant->id }}">
                                                <i class="bi bi-credit-card me-2 text-success"></i>Update Plan
                                            </button>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <!-- Change Status Modal -->
                            <div class="modal fade text-start" id="statusModal{{ $tenant->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content border-0 shadow rounded-4">
                                        <form action="{{ route('saas.tenants.status', $tenant) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <div class="modal-header border-0 pb-0">
                                                <h5 class="modal-title fw-bold">Update Status: {{ $tenant->name }}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body py-3">
                                                <label class="form-label fw-semibold">Tenant Account Status</label>
                                                <select name="status" class="form-select">
                                                    <option value="active" {{ $tenant->status === 'active' ? 'selected' : '' }}>Active (Normal Access)</option>
                                                    <option value="trial" {{ $tenant->status === 'trial' ? 'selected' : '' }}>Trial (Evaluating)</option>
                                                    <option value="suspended" {{ $tenant->status === 'suspended' ? 'selected' : '' }}>Suspended (Lock Access)</option>
                                                    <option value="cancelled" {{ $tenant->status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                                </select>
                                                <p class="small text-muted mt-2 mb-0">
                                                    Suspended tenants receive an instant 403 screen preventing POS or admin actions until reactivated.
                                                </p>
                                            </div>
                                            <div class="modal-footer border-0 pt-0">
                                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-primary">Save Changes</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Update Plan Modal -->
                            <div class="modal fade text-start" id="planModal{{ $tenant->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content border-0 shadow rounded-4">
                                        <form action="{{ route('saas.tenants.plan', $tenant) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <div class="modal-header border-0 pb-0">
                                                <h5 class="modal-title fw-bold">Subscription Plan: {{ $tenant->name }}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body py-3">
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Select Subscription Plan</label>
                                                    <select name="plan_id" class="form-select" required>
                                                        @foreach($plans as $plan)
                                                            <option value="{{ $plan->id }}" {{ $sub?->plan_id === $plan->id ? 'selected' : '' }}>
                                                                {{ $plan->name }} (${{ number_format($plan->price, 2) }}/{{ $plan->billing_cycle }})
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Subscription Status</label>
                                                    <select name="status" class="form-select" required>
                                                        <option value="active" {{ ($sub?->status ?? 'active') === 'active' ? 'selected' : '' }}>Active</option>
                                                        <option value="trial" {{ ($sub?->status ?? '') === 'trial' ? 'selected' : '' }}>Trial</option>
                                                        <option value="past_due" {{ ($sub?->status ?? '') === 'past_due' ? 'selected' : '' }}>Past Due</option>
                                                        <option value="cancelled" {{ ($sub?->status ?? '') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                                    </select>
                                                </div>
                                                <div>
                                                    <label class="form-label fw-semibold">Extend Duration (Days)</label>
                                                    <input type="number" name="extend_days" class="form-control" placeholder="e.g. 30" min="1" max="365">
                                                    <span class="small text-muted">Leave empty to use plan standard period (1 month / 1 year).</span>
                                                </div>
                                            </div>
                                            <div class="modal-footer border-0 pt-0">
                                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-success">Update Subscription</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="ph-duotone ph-magnifying-glass fs-1 d-block mb-2 text-secondary"></i>
                            No food points found matching your search.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($tenants->hasPages())
            <div class="p-3 border-top d-flex justify-content-end">
                {{ $tenants->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
