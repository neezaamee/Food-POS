@extends('layouts.app')

@section('title', 'Subscription Plans & Pricing - SaaS Admin')

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Header -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('saas.dashboard') }}">SaaS Admin</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Plans & Pricing</li>
                </ol>
            </nav>
            <h1 class="h3 fw-bold text-dark mb-0">Subscription Plans & Feature Gates</h1>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('saas.dashboard') }}" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2">
                <i class="ph-duotone ph-arrow-left"></i>
                <span>Overview</span>
            </a>
            <button class="btn btn-primary d-inline-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#createPlanModal">
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
        <div class="col-lg-4 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden {{ $plan->slug === 'professional' ? 'border border-2 border-primary' : '' }}">
                @if($plan->slug === 'professional')
                    <div class="bg-primary text-white text-center py-1 small fw-bold text-uppercase">
                        Most Popular
                    </div>
                @endif
                <div class="card-body p-4 d-flex flex-column">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h4 class="fw-bold text-dark mb-0">{{ $plan->name }}</h4>
                        @if($plan->is_active)
                            <span class="badge bg-success bg-opacity-10 text-success fw-semibold">Active</span>
                        @else
                            <span class="badge bg-secondary">Disabled</span>
                        @endif
                    </div>

                    <div class="mb-3">
                        <span class="display-6 fw-bold text-dark">${{ number_format($plan->price, 0) }}</span>
                        <span class="text-muted">/ {{ $plan->billing_cycle }}</span>
                    </div>

                    <p class="text-muted small mb-4">{{ $plan->description ?? 'Full-featured food point POS management.' }}</p>

                    <!-- Limits -->
                    <div class="p-3 bg-light rounded-3 mb-4">
                        <div class="d-flex justify-content-between py-1 border-bottom border-light-subtle small">
                            <span class="text-muted">Products Limit</span>
                            <strong class="text-dark">{{ $plan->max_products ? number_format($plan->max_products) : 'Unlimited' }}</strong>
                        </div>
                        <div class="d-flex justify-content-between py-1 border-bottom border-light-subtle small">
                            <span class="text-muted">Users / Staff</span>
                            <strong class="text-dark">{{ $plan->max_users ? number_format($plan->max_users) : 'Unlimited' }}</strong>
                        </div>
                        <div class="d-flex justify-content-between py-1 border-bottom border-light-subtle small">
                            <span class="text-muted">Orders / Month</span>
                            <strong class="text-dark">{{ $plan->max_orders_per_month ? number_format($plan->max_orders_per_month) : 'Unlimited' }}</strong>
                        </div>
                        <div class="d-flex justify-content-between py-1 small">
                            <span class="text-muted">Tables Limit</span>
                            <strong class="text-dark">{{ $plan->max_tables ? number_format($plan->max_tables) : 'Unlimited' }}</strong>
                        </div>
                    </div>

                    <!-- Feature List -->
                    <div class="mb-4 flex-grow-1">
                        <span class="fw-semibold text-dark small text-uppercase d-block mb-2">Enabled Features:</span>
                        @php
                            $features = $plan->features ?? [];
                        @endphp
                        <ul class="list-unstyled small mb-0">
                            <li class="mb-2 d-flex align-items-center gap-2">
                                <i class="bi bi-check-circle-fill text-success"></i> POS Terminal & Receipts
                            </li>
                            <li class="mb-2 d-flex align-items-center gap-2 {{ in_array('kot', $features) ? '' : 'text-muted' }}">
                                <i class="bi {{ in_array('kot', $features) ? 'bi-check-circle-fill text-success' : 'bi-x-circle text-muted' }}"></i>
                                Kitchen Display (KOT)
                            </li>
                            <li class="mb-2 d-flex align-items-center gap-2 {{ in_array('inventory', $features) ? '' : 'text-muted' }}">
                                <i class="bi {{ in_array('inventory', $features) ? 'bi-check-circle-fill text-success' : 'bi-x-circle text-muted' }}"></i>
                                Inventory & Recipes
                            </li>
                            <li class="mb-2 d-flex align-items-center gap-2 {{ in_array('whatsapp', $features) ? '' : 'text-muted' }}">
                                <i class="bi {{ in_array('whatsapp', $features) ? 'bi-check-circle-fill text-success' : 'bi-x-circle text-muted' }}"></i>
                                WhatsApp Invoicing
                            </li>
                            <li class="mb-2 d-flex align-items-center gap-2 {{ in_array('accounting', $features) ? '' : 'text-muted' }}">
                                <i class="bi {{ in_array('accounting', $features) ? 'bi-check-circle-fill text-success' : 'bi-x-circle text-muted' }}"></i>
                                Double-Entry Accounting
                            </li>
                            <li class="mb-2 d-flex align-items-center gap-2 {{ in_array('fbr', $features) ? '' : 'text-muted' }}">
                                <i class="bi {{ in_array('fbr', $features) ? 'bi-check-circle-fill text-success' : 'bi-x-circle text-muted' }}"></i>
                                FBR Digital Invoicing
                            </li>
                        </ul>
                    </div>

                    <div class="d-flex align-items-center justify-content-between pt-3 border-top">
                        <span class="small text-muted">{{ $plan->subscriptions_count }} Subscribers</span>
                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editPlanModal{{ $plan->id }}">
                            <i class="bi bi-pencil me-1"></i>Edit Plan
                        </button>
                    </div>
                </div>
            </div>

            <!-- Edit Plan Modal -->
            <div class="modal fade text-start" id="editPlanModal{{ $plan->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content border-0 shadow rounded-4">
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
                                        <label class="form-label fw-semibold">Plan Name</label>
                                        <input type="text" name="name" class="form-control" value="{{ $plan->name }}" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">Price ($)</label>
                                        <input type="number" step="0.01" name="price" class="form-control" value="{{ $plan->price }}" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">Billing Cycle</label>
                                        <select name="billing_cycle" class="form-select" required>
                                            <option value="monthly" {{ $plan->billing_cycle === 'monthly' ? 'selected' : '' }}>Monthly</option>
                                            <option value="yearly" {{ $plan->billing_cycle === 'yearly' ? 'selected' : '' }}>Yearly</option>
                                            <option value="lifetime" {{ $plan->billing_cycle === 'lifetime' ? 'selected' : '' }}>Lifetime</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="row g-3 mb-3">
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">Max Products</label>
                                        <input type="number" name="max_products" class="form-control" value="{{ $plan->max_products }}" placeholder="Empty for unlimited">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">Max Users</label>
                                        <input type="number" name="max_users" class="form-control" value="{{ $plan->max_users }}" placeholder="Empty for unlimited">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">Max Orders/Mo</label>
                                        <input type="number" name="max_orders_per_month" class="form-control" value="{{ $plan->max_orders_per_month }}" placeholder="Empty for unlimited">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">Max Tables</label>
                                        <input type="number" name="max_tables" class="form-control" value="{{ $plan->max_tables }}" placeholder="Empty for unlimited">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Feature Gates</label>
                                    <div class="row g-2">
                                        @php
                                            $currentFeatures = $plan->features ?? [];
                                        @endphp
                                        <div class="col-md-4">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="features[]" value="pos" id="ef_pos{{ $plan->id }}" {{ in_array('pos', $currentFeatures) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="ef_pos{{ $plan->id }}">POS Terminal</label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="features[]" value="kot" id="ef_kot{{ $plan->id }}" {{ in_array('kot', $currentFeatures) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="ef_kot{{ $plan->id }}">Kitchen Display (KOT)</label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="features[]" value="inventory" id="ef_inv{{ $plan->id }}" {{ in_array('inventory', $currentFeatures) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="ef_inv{{ $plan->id }}">Inventory & Purchases</label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="features[]" value="recipes" id="ef_rec{{ $plan->id }}" {{ in_array('recipes', $currentFeatures) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="ef_rec{{ $plan->id }}">Recipe Management</label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="features[]" value="whatsapp" id="ef_wa{{ $plan->id }}" {{ in_array('whatsapp', $currentFeatures) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="ef_wa{{ $plan->id }}">WhatsApp Invoicing</label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="features[]" value="accounting" id="ef_acc{{ $plan->id }}" {{ in_array('accounting', $currentFeatures) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="ef_acc{{ $plan->id }}">Accounting Ledgers</label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="features[]" value="reports" id="ef_rep{{ $plan->id }}" {{ in_array('reports', $currentFeatures) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="ef_rep{{ $plan->id }}">Advanced Reports</label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="features[]" value="fbr" id="ef_fbr{{ $plan->id }}" {{ in_array('fbr', $currentFeatures) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="ef_fbr{{ $plan->id }}">FBR Invoicing</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Description</label>
                                    <textarea name="description" class="form-control" rows="2">{{ $plan->description }}</textarea>
                                </div>

                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="activeSwitch{{ $plan->id }}" {{ $plan->is_active ? 'checked' : '' }}>
                                    <label class="form-check-label fw-semibold" for="activeSwitch{{ $plan->id }}">Plan is active & visible</label>
                                </div>
                            </div>
                            <div class="modal-footer border-0 pt-0">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Create Plan Modal -->
    <div class="modal fade" id="createPlanModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow rounded-4">
                <form action="{{ route('saas.plans.store') }}" method="POST">
                    @csrf
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold">Create New Subscription Plan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body py-3">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Plan Name</label>
                                <input type="text" name="name" class="form-control" placeholder="e.g. Starter, Premium" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Price ($)</label>
                                <input type="number" step="0.01" name="price" class="form-control" placeholder="29.00" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Billing Cycle</label>
                                <select name="billing_cycle" class="form-select" required>
                                    <option value="monthly">Monthly</option>
                                    <option value="yearly">Yearly</option>
                                    <option value="lifetime">Lifetime</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Max Products</label>
                                <input type="number" name="max_products" class="form-control" placeholder="Unlimited if blank">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Max Users</label>
                                <input type="number" name="max_users" class="form-control" placeholder="Unlimited if blank">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Max Orders/Mo</label>
                                <input type="number" name="max_orders_per_month" class="form-control" placeholder="Unlimited if blank">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Max Tables</label>
                                <input type="number" name="max_tables" class="form-control" placeholder="Unlimited if blank">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Enabled Features</label>
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="features[]" value="pos" id="cf_pos" checked>
                                        <label class="form-check-label" for="cf_pos">POS Terminal</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="features[]" value="kot" id="cf_kot" checked>
                                        <label class="form-check-label" for="cf_kot">Kitchen Display (KOT)</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="features[]" value="inventory" id="cf_inv" checked>
                                        <label class="form-check-label" for="cf_inv">Inventory & Purchases</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="features[]" value="recipes" id="cf_rec" checked>
                                        <label class="form-check-label" for="cf_rec">Recipe Management</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="features[]" value="whatsapp" id="cf_wa" checked>
                                        <label class="form-check-label" for="cf_wa">WhatsApp Invoicing</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="features[]" value="accounting" id="cf_acc" checked>
                                        <label class="form-check-label" for="cf_acc">Accounting Ledgers</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="features[]" value="reports" id="cf_rep" checked>
                                        <label class="form-check-label" for="cf_rep">Advanced Reports</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="features[]" value="fbr" id="cf_fbr">
                                        <label class="form-check-label" for="cf_fbr">FBR Invoicing</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Description</label>
                            <textarea name="description" class="form-control" rows="2" placeholder="Brief plan summary..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Plan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
