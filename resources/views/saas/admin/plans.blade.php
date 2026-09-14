@extends('layouts.app')

@section('title', 'Subscription Plans & Feature Gates - SaaS Admin')

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Header -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('saas.dashboard') }}">SaaS Admin</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Plans & Features</li>
                </ol>
            </nav>
            <h1 class="h3 fw-bold text-dark mb-0">Subscription Plans & Feature Gates</h1>
            <p class="text-muted small mb-0">Define subscription tiers, set resource limits (products, users, orders, tables), and configure module entitlements.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('saas.tenants.index') }}" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2">
                <i class="ph-duotone ph-buildings"></i>
                <span>Businesses</span>
            </a>
            <button class="btn btn-primary d-inline-flex align-items-center gap-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#createPlanModal">
                <i class="ph-duotone ph-plus-circle"></i>
                <span>Create New Plan</span>
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Plans Cards Row -->
    <div class="row g-4 mb-4">
        @foreach($plans as $plan)
        @php
            $features = $plan->features ?? [];
            $isWildcard = in_array('*', $features, true);
        @endphp
        <div class="col-lg-4 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden {{ $plan->is_popular ? 'border border-2 border-primary' : '' }}">
                @if($plan->is_popular)
                    <div class="bg-primary text-white text-center py-1 small fw-bold text-uppercase">
                        Recommended
                    </div>
                @endif
                <div class="card-body p-4 d-flex flex-column">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h4 class="fw-bold text-dark mb-0">{{ $plan->name }}</h4>
                        @if($plan->is_active)
                            <span class="badge badge-soft-success">Active</span>
                        @else
                            <span class="badge bg-secondary text-white">Disabled</span>
                        @endif
                    </div>

                    <div class="mb-3">
                        <span class="display-6 fw-bold text-dark">${{ number_format($plan->price_monthly, 2) }}</span>
                        <span class="text-muted small">/ month</span>
                    </div>

                    <p class="text-muted small mb-4">{{ $plan->description ?? 'Full-featured food point POS management.' }}</p>

                    <!-- Limits Box -->
                    <div class="p-3 bg-light rounded-3 mb-4 border">
                        <div class="d-flex justify-content-between py-1 border-bottom border-light-subtle small">
                            <span class="text-muted">Staff / Users</span>
                            <strong class="text-dark">{{ $plan->max_users > 0 ? number_format($plan->max_users) : 'Unlimited' }}</strong>
                        </div>
                        <div class="d-flex justify-content-between py-1 border-bottom border-light-subtle small">
                            <span class="text-muted">Menu Products</span>
                            <strong class="text-dark">{{ $plan->max_products > 0 ? number_format($plan->max_products) : 'Unlimited' }}</strong>
                        </div>
                        <div class="d-flex justify-content-between py-1 border-bottom border-light-subtle small">
                            <span class="text-muted">Monthly Orders</span>
                            <strong class="text-dark">{{ $plan->max_monthly_orders > 0 ? number_format($plan->max_monthly_orders) : 'Unlimited' }}</strong>
                        </div>
                        <div class="d-flex justify-content-between py-1 small">
                            <span class="text-muted">Floor Tables</span>
                            <strong class="text-dark">{{ $plan->max_tables > 0 ? number_format($plan->max_tables) : 'Unlimited' }}</strong>
                        </div>
                    </div>

                    <!-- Entitled Features Preview -->
                    <div class="mb-4 flex-grow-1">
                        <span class="fw-semibold text-dark small text-uppercase d-block mb-2">Included Modules:</span>
                        <div class="d-flex flex-wrap gap-1">
                            @if($isWildcard)
                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1">
                                    <i class="bi bi-stars me-1"></i>All Platform Features Included
                                </span>
                            @else
                                @foreach($allFeatures as $feat)
                                    @php $included = in_array($feat->code, $features, true); @endphp
                                    <span class="badge {{ $included ? 'bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25' : 'bg-light text-muted border' }} small">
                                        <i class="bi {{ $included ? 'bi-check' : 'bi-x' }}"></i> {{ $feat->name }}
                                    </span>
                                @endforeach
                            @endif
                        </div>
                    </div>

                    <!-- Card Actions -->
                    <div class="d-flex align-items-center justify-content-between pt-3 border-top gap-2">
                        <span class="small text-muted">{{ $plan->subscriptions_count }} Active Stores</span>
                        <div class="d-flex gap-1">
                            <form action="{{ route('saas.plans.toggle', $plan) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-sm {{ $plan->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}" title="{{ $plan->is_active ? 'Disable plan' : 'Enable plan' }}">
                                    <i class="bi {{ $plan->is_active ? 'bi-slash-circle' : 'bi-check-circle' }}"></i>
                                    {{ $plan->is_active ? 'Disable' : 'Enable' }}
                                </button>
                            </form>
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editPlanModal{{ $plan->id }}">
                                <i class="bi bi-pencil me-1"></i>Edit
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Edit Plan Modal -->
            <div class="modal fade text-start" id="editPlanModal{{ $plan->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content border-0 shadow-lg rounded-4">
                        <form action="{{ route('saas.plans.update', $plan) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="modal-header border-0 pb-0">
                                <h5 class="modal-title fw-bold">Edit Plan: {{ $plan->name }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body py-3">
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold small">Plan Name <span class="text-danger">*</span></label>
                                        <input type="text" name="name" class="form-control" value="{{ $plan->name }}" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold small">Monthly Price ($) <span class="text-danger">*</span></label>
                                        <input type="number" step="0.01" name="price" class="form-control" value="{{ $plan->price_monthly }}" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold small">Billing Cycle</label>
                                        <select name="billing_cycle" class="form-select" required>
                                            <option value="monthly" selected>Monthly</option>
                                            <option value="yearly">Yearly</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="row g-3 mb-3">
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold small">Max Users (0 = Unlimited)</label>
                                        <input type="number" name="max_users" class="form-control" value="{{ $plan->max_users }}" min="0">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold small">Max Products (0 = Unlimited)</label>
                                        <input type="number" name="max_products" class="form-control" value="{{ $plan->max_products }}" min="0">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold small">Max Orders/Mo (0 = Unlimited)</label>
                                        <input type="number" name="max_orders_per_month" class="form-control" value="{{ $plan->max_monthly_orders }}" min="0">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold small">Max Tables (0 = Unlimited)</label>
                                        <input type="number" name="max_tables" class="form-control" value="{{ $plan->max_tables }}" min="0">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold small">Plan Description</label>
                                    <input type="text" name="description" class="form-control" value="{{ $plan->description }}">
                                </div>

                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActiveSwitch{{ $plan->id }}" {{ $plan->is_active ? 'checked' : '' }}>
                                        <label class="form-check-label fw-semibold small" for="isActiveSwitch{{ $plan->id }}">Active & Available for new subscriptions</label>
                                    </div>
                                </div>

                                <!-- Feature Checkboxes Matrix -->
                                <div class="mb-2">
                                    <label class="form-label fw-semibold small d-block mb-2">Module Entitlements (Check to Include)</label>
                                    @php
                                        $grouped = $allFeatures->groupBy('group');
                                    @endphp
                                    @foreach($grouped as $groupName => $groupFeatures)
                                        <div class="p-3 bg-light rounded-3 mb-2 border">
                                            <div class="fw-bold text-uppercase small text-primary mb-2">
                                                {{ ucfirst(str_replace('_', ' ', $groupName)) }}
                                            </div>
                                            <div class="row g-2">
                                                @foreach($groupFeatures as $gf)
                                                @php
                                                    $checked = $isWildcard || in_array($gf->code, $features, true);
                                                @endphp
                                                <div class="col-md-6">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="features[]" value="{{ $gf->code }}" id="edit_f_{{ $plan->id }}_{{ str_replace('.', '_', $gf->code) }}" {{ $checked ? 'checked' : '' }}>
                                                        <label class="form-check-label small" for="edit_f_{{ $plan->id }}_{{ str_replace('.', '_', $gf->code) }}">
                                                            <strong>{{ $gf->name }}</strong>
                                                            <span class="text-muted d-block" style="font-size: 0.75rem;">{{ $gf->description }}</span>
                                                        </label>
                                                    </div>
                                                </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="modal-footer border-0 pt-0">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">Update Plan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>

<!-- Modal: Create New Plan -->
<div class="modal fade" id="createPlanModal" tabindex="-1" aria-labelledby="createPlanModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form action="{{ route('saas.plans.store') }}" method="POST">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" id="createPlanModalLabel">
                        <i class="ph-duotone ph-plus-circle text-primary me-2"></i>Create Subscription Plan
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-3">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Plan Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. Starter, Growth, Enterprise" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Price ($/Month) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="price" class="form-control" placeholder="e.g. 29.00" required min="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Billing Cycle</label>
                            <select name="billing_cycle" class="form-select" required>
                                <option value="monthly" selected>Monthly</option>
                                <option value="yearly">Yearly</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Max Users (0 = Unlimited)</label>
                            <input type="number" name="max_users" class="form-control" value="0" min="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Max Products (0 = Unlimited)</label>
                            <input type="number" name="max_products" class="form-control" value="0" min="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Max Orders/Mo (0 = Unlimited)</label>
                            <input type="number" name="max_orders_per_month" class="form-control" value="0" min="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Max Tables (0 = Unlimited)</label>
                            <input type="number" name="max_tables" class="form-control" value="0" min="0">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Plan Description</label>
                        <input type="text" name="description" class="form-control" placeholder="Brief plan summary for business owners">
                    </div>

                    <!-- Feature Checkboxes Matrix -->
                    <div class="mb-2">
                        <label class="form-label fw-semibold small d-block mb-2">Module Entitlements</label>
                        @php
                            $grouped = $allFeatures->groupBy('group');
                        @endphp
                        @foreach($grouped as $groupName => $groupFeatures)
                            <div class="p-3 bg-light rounded-3 mb-2 border">
                                <div class="fw-bold text-uppercase small text-primary mb-2">
                                    {{ ucfirst(str_replace('_', ' ', $groupName)) }}
                                </div>
                                <div class="row g-2">
                                    @foreach($groupFeatures as $gf)
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="features[]" value="{{ $gf->code }}" id="create_f_{{ str_replace('.', '_', $gf->code) }}" checked>
                                            <label class="form-check-label small" for="create_f_{{ str_replace('.', '_', $gf->code) }}">
                                                <strong>{{ $gf->name }}</strong>
                                                <span class="text-muted d-block" style="font-size: 0.75rem;">{{ $gf->description }}</span>
                                            </label>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Create Plan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
