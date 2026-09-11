@extends('layouts.app')

@section('title', 'Tenant Details - ' . $tenant->name)

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Breadcrumb & Top Bar -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('saas.dashboard') }}">SaaS Admin</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('saas.tenants.index') }}">Tenants</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $tenant->name }}</li>
                </ol>
            </nav>
            <div class="d-flex align-items-center gap-3">
                <h1 class="h3 fw-bold text-dark mb-0">{{ $tenant->name }}</h1>
                @if($tenant->status === 'active')
                    <span class="badge badge-soft-success">Active</span>
                @elseif($tenant->status === 'trial')
                    <span class="badge badge-soft-warning">Trial</span>
                @elseif($tenant->status === 'suspended')
                    <span class="badge badge-soft-danger">Suspended</span>
                @else
                    <span class="badge bg-secondary">{{ ucfirst($tenant->status) }}</span>
                @endif
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('saas.tenants.impersonate', $tenant) }}" class="btn btn-warning d-inline-flex align-items-center gap-2 fw-semibold">
                <i class="ph-duotone ph-user-switch"></i>
                <span>Impersonate / Login As Tenant</span>
            </a>
            <a href="{{ route('saas.tenants.index') }}" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2">
                <i class="ph-duotone ph-arrow-left"></i>
                <span>Back to Tenants</span>
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Metrics Row -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 h-100">
                <span class="text-muted small fw-semibold text-uppercase mb-1">Total Sales (GMV)</span>
                <h3 class="fw-bold text-dark mb-0">${{ number_format($salesTotal, 2) }}</h3>
                <span class="small text-muted mt-1">Completed orders</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 h-100">
                <span class="text-muted small fw-semibold text-uppercase mb-1">Orders Count</span>
                <h3 class="fw-bold text-dark mb-0">{{ number_format($ordersCount) }}</h3>
                <span class="small text-muted mt-1">All-time processed orders</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 h-100">
                <span class="text-muted small fw-semibold text-uppercase mb-1">Active Products</span>
                <h3 class="fw-bold text-dark mb-0">{{ number_format($productsCount) }}</h3>
                <span class="small text-muted mt-1">Catalog items in POS</span>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 h-100">
                <span class="text-muted small fw-semibold text-uppercase mb-1">Users & Staff</span>
                <h3 class="fw-bold text-dark mb-0">{{ $users->count() }}</h3>
                <span class="small text-muted mt-1">Cashiers, managers, waiters</span>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Profile & Meta -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                <h5 class="fw-bold text-dark mb-3">
                    <i class="ph-duotone ph-info me-2 text-primary"></i>Restaurant Profile
                </h5>
                <dl class="row mb-0">
                    <dt class="col-sm-4 text-muted">Subdomain / Slug</dt>
                    <dd class="col-sm-8"><code class="text-primary bg-light px-2 py-1 rounded">{{ $tenant->slug }}</code></dd>

                    <dt class="col-sm-4 text-muted">Contact Email</dt>
                    <dd class="col-sm-8 text-dark">{{ $tenant->email ?? 'Not provided' }}</dd>

                    <dt class="col-sm-4 text-muted">Contact Phone</dt>
                    <dd class="col-sm-8 text-dark">{{ $tenant->phone ?? 'Not provided' }}</dd>

                    <dt class="col-sm-4 text-muted">Currency</dt>
                    <dd class="col-sm-8 text-dark">{{ $tenant->currency ?? 'USD' }}</dd>

                    <dt class="col-sm-4 text-muted">Timezone</dt>
                    <dd class="col-sm-8 text-dark">{{ $tenant->timezone ?? 'UTC' }}</dd>

                    <dt class="col-sm-4 text-muted">Created At</dt>
                    <dd class="col-sm-8 text-dark">{{ $tenant->created_at->format('M d, Y - h:i A') }}</dd>
                </dl>
            </div>
        </div>

        <!-- Subscription Overview -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h5 class="fw-bold text-dark mb-0">
                        <i class="ph-duotone ph-credit-card me-2 text-success"></i>Subscription Plan
                    </h5>
                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editPlanModal">
                        Change Plan
                    </button>
                </div>

                @if($currentSubscription && $currentSubscription->plan)
                    @php $plan = $currentSubscription->plan; @endphp
                    <div class="p-3 bg-light rounded-4 mb-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h4 class="fw-bold text-primary mb-0">{{ $plan->name }}</h4>
                                <span class="text-muted small">${{ number_format($plan->price, 2) }} / {{ $plan->billing_cycle }}</span>
                            </div>
                            <span class="badge {{ $currentSubscription->status === 'active' ? 'badge-soft-success' : 'badge-soft-warning' }} fs-6 px-3 py-2">
                                {{ ucfirst($currentSubscription->status) }}
                            </span>
                        </div>
                    </div>

                    <div class="row g-2 mb-3 small">
                        <div class="col-6">
                            <div class="p-2 border rounded-3 bg-white">
                                <span class="text-muted d-block">Max Products</span>
                                <strong class="text-dark">{{ $plan->max_products ?? 'Unlimited' }}</strong>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 border rounded-3 bg-white">
                                <span class="text-muted d-block">Max Users</span>
                                <strong class="text-dark">{{ $plan->max_users ?? 'Unlimited' }}</strong>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 border rounded-3 bg-white">
                                <span class="text-muted d-block">Max Orders/Mo</span>
                                <strong class="text-dark">{{ $plan->max_orders_per_month ?? 'Unlimited' }}</strong>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 border rounded-3 bg-white">
                                <span class="text-muted d-block">Tables Limit</span>
                                <strong class="text-dark">{{ $plan->max_tables ?? 'Unlimited' }}</strong>
                            </div>
                        </div>
                    </div>

                    <div class="small text-muted">
                        <div><strong>Valid Until:</strong> {{ $currentSubscription->ends_at ? $currentSubscription->ends_at->format('M d, Y') : 'Lifetime / Unlimited' }}</div>
                        @if($currentSubscription->trial_ends_at)
                            <div><strong>Trial Ends:</strong> {{ $currentSubscription->trial_ends_at->format('M d, Y') }}</div>
                        @endif
                    </div>
                @else
                    <div class="text-center py-4 text-muted">
                        <p class="mb-2">No active subscription plan attached to this tenant.</p>
                        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editPlanModal">Assign Plan</button>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Tenant Users List -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <div class="card-header bg-white border-0 py-3 px-4">
            <h5 class="fw-bold mb-0 text-dark">
                <i class="ph-duotone ph-users me-2 text-primary"></i>Restaurant Staff & Users
            </h5>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted small text-uppercase">
                    <tr>
                        <th class="ps-4">User</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Created</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                    <tr>
                        <td class="ps-4 fw-semibold text-dark">{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td><span class="badge bg-light text-dark border">{{ ucfirst($user->role ?? 'User') }}</span></td>
                        <td>
                            @if($user->status === 'active')
                                <span class="badge badge-soft-success">Active</span>
                            @else
                                <span class="badge badge-soft-danger">{{ ucfirst($user->status) }}</span>
                            @endif
                        </td>
                        <td class="text-end pe-4 text-muted small">{{ $user->created_at->format('M d, Y') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">No users found under this tenant.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Edit Plan Modal -->
    <div class="modal fade" id="editPlanModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow rounded-4">
                <form action="{{ route('saas.tenants.plan', $tenant) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold">Update Subscription: {{ $tenant->name }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body py-3">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Select Subscription Plan</label>
                            <select name="plan_id" class="form-select" required>
                                @foreach($allPlans as $p)
                                    <option value="{{ $p->id }}" {{ $currentSubscription?->plan_id === $p->id ? 'selected' : '' }}>
                                        {{ $p->name }} (${{ number_format($p->price, 2) }}/{{ $p->billing_cycle }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Status</label>
                            <select name="status" class="form-select" required>
                                <option value="active" {{ ($currentSubscription?->status ?? 'active') === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="trial" {{ ($currentSubscription?->status ?? '') === 'trial' ? 'selected' : '' }}>Trial</option>
                                <option value="past_due" {{ ($currentSubscription?->status ?? '') === 'past_due' ? 'selected' : '' }}>Past Due</option>
                                <option value="cancelled" {{ ($currentSubscription?->status ?? '') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label fw-semibold">Extend Duration (Days)</label>
                            <input type="number" name="extend_days" class="form-control" placeholder="e.g. 30" min="1" max="365">
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Subscription</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
