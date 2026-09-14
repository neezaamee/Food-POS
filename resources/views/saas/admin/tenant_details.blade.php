@extends('layouts.app')

@section('title', 'Business Details - ' . $tenant->name)

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Breadcrumb & Top Bar -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('saas.dashboard') }}">SaaS Admin</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('saas.tenants.index') }}">Businesses</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $tenant->name }}</li>
                </ol>
            </nav>
            <div class="d-flex align-items-center gap-3">
                <h1 class="h3 fw-bold text-dark mb-0">{{ $tenant->name }}</h1>
                @if($tenant->isDisabled())
                    <span class="badge bg-secondary text-white px-2 py-1"><i class="bi bi-slash-circle me-1"></i>Disabled</span>
                @elseif($tenant->isSuspended())
                    <span class="badge badge-soft-danger px-2 py-1"><i class="bi bi-shield-x me-1"></i>Suspended</span>
                @elseif($tenant->status === 'trial')
                    <span class="badge badge-soft-warning px-2 py-1"><i class="bi bi-clock me-1"></i>Trial</span>
                @elseif($tenant->status === 'active')
                    <span class="badge badge-soft-success px-2 py-1"><i class="bi bi-check-circle me-1"></i>Active</span>
                @else
                    <span class="badge bg-secondary px-2 py-1">{{ ucfirst($tenant->status) }}</span>
                @endif
                <span class="badge bg-light text-dark border px-2 py-1 small">
                    {{ ucfirst(str_replace('_', ' ', $tenant->business_type ?? 'restaurant')) }}
                </span>
            </div>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-primary d-inline-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#changeStatusModal">
                <i class="ph-duotone ph-toggles"></i>
                <span>Status & Access</span>
            </button>
            <a href="{{ route('saas.tenants.impersonate', $tenant) }}" class="btn btn-warning d-inline-flex align-items-center gap-2 fw-semibold">
                <i class="ph-duotone ph-user-switch"></i>
                <span>Login As Store</span>
            </a>
            <a href="{{ route('saas.tenants.index') }}" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2">
                <i class="ph-duotone ph-arrow-left"></i>
                <span>Back</span>
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($tenant->isDisabled())
        <div class="alert alert-secondary border-0 rounded-4 shadow-sm mb-4 d-flex align-items-center gap-3">
            <i class="bi bi-slash-circle-fill text-secondary fs-3"></i>
            <div>
                <strong>This business account is currently disabled.</strong>
                <p class="mb-0 small text-muted">Staff and owners cannot access the POS terminal or back-office. Use the "Status & Access" button to enable.</p>
            </div>
        </div>
    @elseif($tenant->isSuspended())
        <div class="alert alert-danger border-0 rounded-4 shadow-sm mb-4 d-flex align-items-center gap-3">
            <i class="bi bi-shield-x text-danger fs-3"></i>
            <div>
                <strong>This business account is suspended.</strong>
                <p class="mb-0 small text-muted">All POS sessions are locked out with a suspension screen. Update subscription or reactivate status to restore service.</p>
            </div>
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
                <span class="text-muted small fw-semibold text-uppercase mb-1">Orders Processed</span>
                <h3 class="fw-bold text-dark mb-0">{{ number_format($ordersCount) }}</h3>
                <span class="small text-muted mt-1">All-time count</span>
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
                <span class="text-muted small fw-semibold text-uppercase mb-1">Staff Users</span>
                <h3 class="fw-bold text-dark mb-0">{{ $users->count() }}</h3>
                <span class="small text-muted mt-1">Cashiers, managers, waiters</span>
            </div>
        </div>
    </div>

    <!-- Details Row: Profile + Owner + Plan Quotas -->
    <div class="row g-4 mb-4">
        <!-- Business Profile -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                <h5 class="fw-bold text-dark mb-3">
                    <i class="ph-duotone ph-buildings me-2 text-primary"></i>Business Profile
                </h5>
                <dl class="row mb-0">
                    <dt class="col-sm-4 text-muted">Legal / Store Name</dt>
                    <dd class="col-sm-8 text-dark fw-semibold">{{ $tenant->name }}</dd>

                    <dt class="col-sm-4 text-muted">Subdomain / Slug</dt>
                    <dd class="col-sm-8"><code class="text-primary bg-light px-2 py-1 rounded">{{ $tenant->slug }}</code></dd>

                    <dt class="col-sm-4 text-muted">Business Type</dt>
                    <dd class="col-sm-8 text-dark">{{ ucfirst(str_replace('_', ' ', $tenant->business_type ?? 'restaurant')) }}</dd>

                    <dt class="col-sm-4 text-muted">Contact Email</dt>
                    <dd class="col-sm-8 text-dark">{{ $tenant->email ?? 'Not provided' }}</dd>

                    <dt class="col-sm-4 text-muted">Contact Phone</dt>
                    <dd class="col-sm-8 text-dark">{{ $tenant->phone ?? 'Not provided' }}</dd>

                    <dt class="col-sm-4 text-muted">Location</dt>
                    <dd class="col-sm-8 text-dark">
                        {{ $tenant->address ?? 'No street address' }}
                        @if($tenant->city)
                            <span class="d-block text-muted small">{{ $tenant->city }}{{ $tenant->province ? ', ' . $tenant->province : '' }}</span>
                        @endif
                    </dd>

                    <dt class="col-sm-4 text-muted">Currency</dt>
                    <dd class="col-sm-8 text-dark"><span class="badge bg-light text-dark border">{{ $tenant->currency ?? 'PKR' }}</span></dd>

                    <dt class="col-sm-4 text-muted">Created At</dt>
                    <dd class="col-sm-8 text-dark">{{ $tenant->created_at->format('M d, Y - h:i A') }}</dd>
                </dl>
            </div>
        </div>

        <!-- Business Owner Account -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h5 class="fw-bold text-dark mb-0">
                        <i class="ph-duotone ph-user-circle-gear me-2 text-success"></i>Business Owner Account
                    </h5>
                    <a href="{{ route('saas.owners.index') }}" class="btn btn-sm btn-outline-secondary">
                        All Owners
                    </a>
                </div>

                @php
                    $primaryOwner = $tenant->primaryOwner();
                    $allOwners = $tenant->owners;
                @endphp

                @if($primaryOwner)
                    <div class="p-3 bg-light rounded-4 mb-3 border">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-circle bg-success bg-opacity-10 text-success fw-bold d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                    {{ strtoupper(substr($primaryOwner->name, 0, 2)) }}
                                </div>
                                <div>
                                    <div class="d-flex align-items-center gap-2">
                                        <h6 class="fw-bold text-dark mb-0">{{ $primaryOwner->name }}</h6>
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 small">Primary Owner</span>
                                    </div>
                                    <span class="text-muted small">{{ $primaryOwner->email }}</span>
                                    @if($primaryOwner->phone)
                                        <span class="text-muted small d-block">{{ $primaryOwner->phone }}</span>
                                    @endif
                                </div>
                            </div>
                            <span class="badge badge-soft-success">Active</span>
                        </div>
                    </div>

                    @if($allOwners->count() > 1)
                        <div class="small fw-semibold text-muted mb-2">Additional Owners:</div>
                        <ul class="list-group list-group-flush small border rounded-3 mb-0">
                            @foreach($allOwners as $otherOwner)
                                @if($otherOwner->id !== $primaryOwner->id)
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="fw-semibold">{{ $otherOwner->name }}</span>
                                            <span class="text-muted">({{ $otherOwner->email }})</span>
                                        </div>
                                        <span class="badge bg-light text-muted border">Co-Owner</span>
                                    </li>
                                @endif
                            @endforeach
                        </ul>
                    @endif
                @else
                    <div class="text-center py-4 text-muted">
                        <p class="mb-2">No owner account assigned to this business yet.</p>
                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createOwnerModal">
                            Assign Owner
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Plan Quotas & Resource Gauges -->
    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h5 class="fw-bold text-dark mb-1">
                    <i class="ph-duotone ph-chart-donut me-2 text-info"></i>Subscription Plan & Resource Limits
                </h5>
                <p class="text-muted small mb-0">Live consumption against subscribed plan limits.</p>
            </div>
            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editPlanModal">
                <i class="bi bi-credit-card me-1"></i>Change Plan
            </button>
        </div>

        @if($currentSubscription && $currentSubscription->plan)
            @php $plan = $currentSubscription->plan; @endphp
            <div class="row g-3">
                <!-- Users Limit Gauge -->
                <div class="col-md-3">
                    <div class="p-3 bg-light rounded-4 border h-100">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-semibold text-dark small"><i class="bi bi-people me-1 text-primary"></i>Staff Users</span>
                            <span class="badge bg-white border text-dark small">
                                {{ $quotaUsage['users']['used'] }} / {{ $quotaUsage['users']['unlimited'] ? '∞' : $quotaUsage['users']['limit'] }}
                            </span>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $quotaUsage['users']['percentage'] }}%"></div>
                        </div>
                        <span class="text-muted small mt-2 d-block">
                            {{ $quotaUsage['users']['unlimited'] ? 'Unlimited staff accounts' : ($quotaUsage['users']['limit'] - $quotaUsage['users']['used']) . ' user slots available' }}
                        </span>
                    </div>
                </div>

                <!-- Products Limit Gauge -->
                <div class="col-md-3">
                    <div class="p-3 bg-light rounded-4 border h-100">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-semibold text-dark small"><i class="bi bi-box-seam me-1 text-success"></i>Menu Products</span>
                            <span class="badge bg-white border text-dark small">
                                {{ $quotaUsage['products']['used'] }} / {{ $quotaUsage['products']['unlimited'] ? '∞' : $quotaUsage['products']['limit'] }}
                            </span>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: {{ $quotaUsage['products']['percentage'] }}%"></div>
                        </div>
                        <span class="text-muted small mt-2 d-block">
                            {{ $quotaUsage['products']['unlimited'] ? 'Unlimited catalog items' : ($quotaUsage['products']['limit'] - $quotaUsage['products']['used']) . ' products remaining' }}
                        </span>
                    </div>
                </div>

                <!-- Monthly Orders Limit Gauge -->
                <div class="col-md-3">
                    <div class="p-3 bg-light rounded-4 border h-100">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-semibold text-dark small"><i class="bi bi-receipt me-1 text-warning"></i>Monthly Orders</span>
                            <span class="badge bg-white border text-dark small">
                                {{ $quotaUsage['monthly_orders']['used'] }} / {{ $quotaUsage['monthly_orders']['unlimited'] ? '∞' : $quotaUsage['monthly_orders']['limit'] }}
                            </span>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-warning" role="progressbar" style="width: {{ $quotaUsage['monthly_orders']['percentage'] }}%"></div>
                        </div>
                        <span class="text-muted small mt-2 d-block">
                            {{ $quotaUsage['monthly_orders']['unlimited'] ? 'Unlimited POS orders' : ($quotaUsage['monthly_orders']['limit'] - $quotaUsage['monthly_orders']['used']) . ' orders left this month' }}
                        </span>
                    </div>
                </div>

                <!-- Tables Limit Gauge -->
                <div class="col-md-3">
                    <div class="p-3 bg-light rounded-4 border h-100">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-semibold text-dark small"><i class="bi bi-grid-3x3 me-1 text-info"></i>Floor Tables</span>
                            <span class="badge bg-white border text-dark small">
                                {{ $quotaUsage['tables']['used'] }} / {{ $quotaUsage['tables']['unlimited'] ? '∞' : $quotaUsage['tables']['limit'] }}
                            </span>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-info" role="progressbar" style="width: {{ $quotaUsage['tables']['percentage'] }}%"></div>
                        </div>
                        <span class="text-muted small mt-2 d-block">
                            {{ $quotaUsage['tables']['unlimited'] ? 'Unlimited dine-in tables' : ($quotaUsage['tables']['limit'] - $quotaUsage['tables']['used']) . ' tables remaining' }}
                        </span>
                    </div>
                </div>
            </div>
        @else
            <div class="text-muted text-center py-3">No active subscription plan attached.</div>
        @endif
    </div>

    <!-- Feature Access & Overrides Matrix -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <div class="card-header bg-white border-0 py-3 px-4 d-flex justify-content-between align-items-center">
            <div>
                <h5 class="fw-bold mb-1 text-dark">
                    <i class="ph-duotone ph-sliders me-2 text-primary"></i>Feature Entitlements & Custom Overrides
                </h5>
                <p class="text-muted small mb-0">Fine-tune individual feature flags for this specific business. Overrides take precedence over the subscription plan.</p>
            </div>
            <button type="submit" form="featureOverridesForm" class="btn btn-primary btn-sm px-3 shadow-sm">
                <i class="bi bi-check2 me-1"></i>Save Feature Overrides
            </button>
        </div>

        <form id="featureOverridesForm" action="{{ route('saas.tenants.features', $tenant) }}" method="POST">
            @csrf
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-muted small text-uppercase">
                        <tr>
                            <th class="ps-4">Feature Code</th>
                            <th>Feature Name & Description</th>
                            <th>Group</th>
                            <th>Plan Default</th>
                            <th>Effective Access</th>
                            <th class="pe-4 text-end">Super Admin Override</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $overridesMap = $tenant->featureOverrides->pluck('is_enabled', 'feature_code')->toArray();
                        @endphp
                        @foreach($effectiveFeatures as $feat)
                        @php
                            $hasOverride = array_key_exists($feat['code'], $overridesMap);
                            $overrideValue = $hasOverride ? ($overridesMap[$feat['code']] ? 'enable' : 'disable') : 'inherit';
                        @endphp
                        <tr>
                            <td class="ps-4">
                                <code class="text-dark bg-light px-2 py-1 rounded small fw-semibold">{{ $feat['code'] }}</code>
                            </td>
                            <td>
                                <strong class="text-dark d-block">{{ $feat['name'] }}</strong>
                                <span class="text-muted small">{{ $feat['description'] ?? 'Platform module' }}</span>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border small">{{ ucfirst(str_replace('_', ' ', $feat['group'])) }}</span>
                            </td>
                            <td>
                                @if($feat['plan_included'])
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 small">
                                        <i class="bi bi-check-lg me-1"></i>Included
                                    </span>
                                @else
                                    <span class="badge bg-light text-muted border small">Not in Plan</span>
                                @endif
                            </td>
                            <td>
                                @if($feat['is_enabled'])
                                    <span class="badge badge-soft-success px-2 py-1">
                                        <i class="bi bi-unlock-fill me-1"></i>Active
                                    </span>
                                @else
                                    <span class="badge badge-soft-danger px-2 py-1">
                                        <i class="bi bi-lock-fill me-1"></i>Locked
                                    </span>
                                @endif
                                @if($feat['is_overridden'])
                                    <span class="badge bg-warning text-dark ms-1 small">Override</span>
                                @endif
                            </td>
                            <td class="pe-4 text-end">
                                <select name="overrides[{{ $feat['code'] }}]" class="form-select form-select-sm d-inline-block w-auto">
                                    <option value="inherit" {{ $overrideValue === 'inherit' ? 'selected' : '' }}>
                                        Default (Inherit from Plan)
                                    </option>
                                    <option value="enable" {{ $overrideValue === 'enable' ? 'selected' : '' }}>
                                        Force Enable (Override)
                                    </option>
                                    <option value="disable" {{ $overrideValue === 'disable' ? 'selected' : '' }}>
                                        Force Disable (Lockout)
                                    </option>
                                </select>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-light border-0 py-3 px-4 d-flex justify-content-end">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-check2 me-1"></i>Save All Overrides
                </button>
            </div>
        </form>
    </div>

    <!-- Tenant Users List -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <div class="card-header bg-white border-0 py-3 px-4">
            <h5 class="fw-bold mb-0 text-dark">
                <i class="ph-duotone ph-users me-2 text-primary"></i>Store Staff & Users ({{ $users->count() }})
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
</div>

<!-- Modal: Change Status & Access -->
<div class="modal fade" id="changeStatusModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form action="{{ route('saas.tenants.status', $tenant) }}" method="POST">
                @csrf
                @method('PATCH')
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Manage Account Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-3">
                    <label class="form-label fw-semibold">Select Business Status</label>
                    <select name="status" class="form-select mb-3">
                        <option value="active" {{ $tenant->status === 'active' && ! $tenant->isDisabled() ? 'selected' : '' }}>Active (Full POS & Backoffice Access)</option>
                        <option value="trial" {{ $tenant->status === 'trial' ? 'selected' : '' }}>Trial (Evaluating Free Period)</option>
                        <option value="suspended" {{ $tenant->status === 'suspended' ? 'selected' : '' }}>Suspended (Billing Overdue Lockout)</option>
                        <option value="disabled" {{ $tenant->isDisabled() ? 'selected' : '' }}>Disabled (Deactivated by Admin)</option>
                    </select>

                    <div class="p-3 bg-light rounded-3 small border">
                        <div class="fw-semibold text-dark mb-1">Status Enforcement Details:</div>
                        <ul class="text-muted ps-3 mb-0">
                            <li><strong>Disabled:</strong> The business is completely deactivated. No POS or staff actions are permitted.</li>
                            <li><strong>Suspended:</strong> Displays a professional lockout screen informing staff that service is temporarily halted.</li>
                            <li><strong>Active:</strong> Business operates normally according to its subscription quotas.</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Edit Plan -->
<div class="modal fade" id="editPlanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <form action="{{ route('saas.tenants.plan', $tenant) }}" method="POST">
                @csrf
                @method('PATCH')
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Update Subscription Plan: {{ $tenant->name }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-3">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Select Subscription Plan</label>
                        <select name="plan_id" class="form-select" required>
                            @foreach($allPlans as $p)
                                <option value="{{ $p->id }}" {{ $currentSubscription?->plan_id === $p->id ? 'selected' : '' }}>
                                    {{ $p->name }} (${{ number_format($p->price_monthly, 2) }}/mo)
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
                        <span class="small text-muted">Leave empty to use plan standard period (30 days).</span>
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
@endsection
