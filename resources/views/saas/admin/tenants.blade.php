@extends('layouts.app')

@section('title', 'Businesses & Food Points Directory')

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Header -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('saas.dashboard') }}">SaaS Admin</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Businesses</li>
                </ol>
            </nav>
            <h1 class="h3 fw-bold text-dark mb-0">Businesses & Tenants</h1>
            <p class="text-muted small mb-0">Manage registered businesses, assigned owners, subscription plans, and operational statuses.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('saas.owners.index') }}" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2">
                <i class="ph-duotone ph-users-three"></i>
                <span>All Owners</span>
            </a>
            <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#createBusinessModal">
                <i class="ph-duotone ph-plus-circle"></i>
                <span>Add Business & Owner</span>
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
        <form action="{{ route('saas.tenants.index') }}" method="GET" class="row g-3 align-items-center">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text bg-light border-0"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control bg-light border-0" placeholder="Search business, owner, slug, email, city..." value="{{ request('search') }}">
                </div>
            </div>
            <div class="col-md-3">
                <select name="business_type" class="form-select bg-light border-0" onchange="this.form.submit()">
                    <option value="">All Business Types</option>
                    <option value="restaurant" {{ request('business_type') === 'restaurant' ? 'selected' : '' }}>Restaurant</option>
                    <option value="cafe" {{ request('business_type') === 'cafe' ? 'selected' : '' }}>Cafe / Coffee Shop</option>
                    <option value="fast_food" {{ request('business_type') === 'fast_food' ? 'selected' : '' }}>Fast Food</option>
                    <option value="bakery" {{ request('business_type') === 'bakery' ? 'selected' : '' }}>Bakery / Sweets</option>
                    <option value="food_truck" {{ request('business_type') === 'food_truck' ? 'selected' : '' }}>Food Truck</option>
                    <option value="cloud_kitchen" {{ request('business_type') === 'cloud_kitchen' ? 'selected' : '' }}>Cloud Kitchen</option>
                    <option value="other" {{ request('business_type') === 'other' ? 'selected' : '' }}>Other</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select bg-light border-0" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="trial" {{ request('status') === 'trial' ? 'selected' : '' }}>Trial</option>
                    <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
                    <option value="disabled" {{ request('status') === 'disabled' ? 'selected' : '' }}>Disabled</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
                <a href="{{ route('saas.tenants.index') }}" class="btn btn-light">Reset</a>
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
                        <th>Business / Restaurant</th>
                        <th>Business Owner</th>
                        <th>Type & City</th>
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
                        $owner = $tenant->primaryOwner();
                    @endphp
                    <tr>
                        <td class="ps-4 fw-semibold text-muted">#{{ $tenant->id }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-circle bg-primary bg-opacity-10 text-primary fw-bold d-flex align-items-center justify-content-center" style="width: 42px; height: 42px; flex-shrink: 0;">
                                    {{ strtoupper(substr($tenant->name, 0, 2)) }}
                                </div>
                                <div>
                                    <a href="{{ route('saas.tenants.show', $tenant) }}" class="fw-bold text-dark text-decoration-none d-block">
                                        {{ $tenant->name }}
                                    </a>
                                    <div class="text-muted small d-flex align-items-center gap-2">
                                        <code class="text-secondary small">{{ $tenant->slug }}</code>
                                        @if($tenant->phone)
                                            <span>• {{ $tenant->phone }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            @if($owner)
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle bg-success bg-opacity-10 text-success small fw-bold d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                                        <i class="bi bi-person-badge"></i>
                                    </div>
                                    <div>
                                        <span class="fw-semibold text-dark d-block small">{{ $owner->name }}</span>
                                        <span class="text-muted small">{{ $owner->email }}</span>
                                    </div>
                                </div>
                            @else
                                <span class="text-muted small fst-italic">No owner assigned</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border px-2 py-1 small">
                                {{ ucfirst(str_replace('_', ' ', $tenant->business_type ?? 'restaurant')) }}
                            </span>
                            @if($tenant->city)
                                <span class="text-muted small d-block mt-1"><i class="bi bi-geo-alt me-1"></i>{{ $tenant->city }}</span>
                            @endif
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
                        </td>
                        <td class="text-muted small">
                            {{ $tenant->created_at->format('M d, Y') }}
                        </td>
                        <td class="text-end pe-4">
                            <div class="d-flex align-items-center justify-content-end gap-1">
                                <!-- Impersonate / Login as Business -->
                                <a href="{{ route('saas.tenants.impersonate', $tenant) }}" class="btn btn-sm btn-outline-warning d-inline-flex align-items-center gap-1" title="Login / Impersonate this business">
                                    <i class="ph-duotone ph-user-switch"></i>
                                    <span>Login</span>
                                </a>

                                <!-- View Details -->
                                <a href="{{ route('saas.tenants.show', $tenant) }}" class="btn btn-sm btn-outline-secondary" title="View Full Details & Overrides">
                                    <i class="bi bi-eye"></i>
                                </a>

                                <!-- Action Dropdown -->
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light border-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3">
                                        <li><h6 class="dropdown-header">Business Actions</h6></li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('saas.tenants.show', $tenant) }}">
                                                <i class="bi bi-sliders me-2 text-info"></i>Manage Features & Limits
                                            </a>
                                        </li>
                                        <li>
                                            <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#statusModal{{ $tenant->id }}">
                                                <i class="bi bi-toggles me-2 text-primary"></i>Change Status (Enable/Disable)
                                            </button>
                                        </li>
                                        <li>
                                            <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#planModal{{ $tenant->id }}">
                                                <i class="bi bi-credit-card me-2 text-success"></i>Update Subscription Plan
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
                                                <h5 class="modal-title fw-bold">Business Status: {{ $tenant->name }}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body py-3">
                                                <label class="form-label fw-semibold">Account State</label>
                                                <select name="status" class="form-select mb-3">
                                                    <option value="active" {{ $tenant->status === 'active' && ! $tenant->isDisabled() ? 'selected' : '' }}>
                                                        Active (Full POS & Backoffice Access)
                                                    </option>
                                                    <option value="trial" {{ $tenant->status === 'trial' ? 'selected' : '' }}>
                                                        Trial (Free Evaluation Period)
                                                    </option>
                                                    <option value="suspended" {{ $tenant->status === 'suspended' ? 'selected' : '' }}>
                                                        Suspended (Billing Overdue / Non-payment)
                                                    </option>
                                                    <option value="disabled" {{ $tenant->isDisabled() ? 'selected' : '' }}>
                                                        Disabled (Deactivated by Super Admin)
                                                    </option>
                                                </select>

                                                <div class="p-3 bg-light rounded-3 small border">
                                                    <div class="fw-semibold text-dark mb-1">Enforcement Behavior:</div>
                                                    <ul class="text-muted ps-3 mb-0">
                                                        <li><strong>Suspended / Disabled:</strong> Immediately prevents all POS, kitchen, and inventory access. Users see a platform lockout screen.</li>
                                                        <li><strong>Active:</strong> Business operates with plan-allocated quotas and feature sets.</li>
                                                    </ul>
                                                </div>
                                            </div>
                                            <div class="modal-footer border-0 pt-0">
                                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-primary">Save Status</button>
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
                                                                {{ $plan->name }} (${{ number_format($plan->price_monthly, 2) }}/mo)
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
                                                    <span class="small text-muted">Leave empty to use plan standard period (30 days).</span>
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
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="ph-duotone ph-buildings fs-1 d-block mb-2 text-secondary"></i>
                            No businesses found matching your criteria.
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

<!-- Modal: Add Business & Owner -->
<div class="modal fade" id="createBusinessModal" tabindex="-1" aria-labelledby="createBusinessModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form action="{{ route('saas.tenants.store') }}" method="POST">
                @csrf
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title fw-bold" id="createBusinessModalLabel">
                            <i class="ph-duotone ph-plus-circle text-primary me-2"></i>Register Business & Owner
                        </h5>
                        <p class="text-muted small mb-0">Create a business profile, set up the primary business owner account, and assign an initial plan.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-4">
                    <!-- SECTION 1: BUSINESS DETAILS -->
                    <div class="d-flex align-items-center gap-2 mb-3 text-primary fw-bold text-uppercase small">
                        <i class="bi bi-shop"></i>
                        <span>1. Business / Tenant Profile</span>
                        <hr class="flex-grow-1 my-0 text-muted">
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Business Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. Royal Spice Bistro" required value="{{ old('name') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Business Type <span class="text-danger">*</span></label>
                            <select name="business_type" class="form-select" required>
                                <option value="restaurant" {{ old('business_type') === 'restaurant' ? 'selected' : '' }}>Restaurant (Dine-in & Takeaway)</option>
                                <option value="cafe" {{ old('business_type') === 'cafe' ? 'selected' : '' }}>Cafe / Coffee Shop</option>
                                <option value="fast_food" {{ old('business_type') === 'fast_food' ? 'selected' : '' }}>Fast Food / Burger Joint</option>
                                <option value="bakery" {{ old('business_type') === 'bakery' ? 'selected' : '' }}>Bakery / Pastry & Sweets</option>
                                <option value="food_truck" {{ old('business_type') === 'food_truck' ? 'selected' : '' }}>Food Truck / Pop-up</option>
                                <option value="cloud_kitchen" {{ old('business_type') === 'cloud_kitchen' ? 'selected' : '' }}>Cloud Kitchen / Delivery Only</option>
                                <option value="ice_cream" {{ old('business_type') === 'ice_cream' ? 'selected' : '' }}>Ice Cream & Desserts</option>
                                <option value="juice_bar" {{ old('business_type') === 'juice_bar' ? 'selected' : '' }}>Juice Bar / Beverage</option>
                                <option value="other" {{ old('business_type') === 'other' ? 'selected' : '' }}>Other Food Business</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Business Phone</label>
                            <input type="text" name="phone" class="form-control" placeholder="e.g. +92 300 1234567" value="{{ old('phone') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Business Email</label>
                            <input type="email" name="email" class="form-control" placeholder="business@foodpoint.com" value="{{ old('email') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Currency <span class="text-danger">*</span></label>
                            <select name="currency" class="form-select" required>
                                <option value="PKR" {{ old('currency', 'PKR') === 'PKR' ? 'selected' : '' }}>PKR (Rs.)</option>
                                <option value="USD" {{ old('currency') === 'USD' ? 'selected' : '' }}>USD ($)</option>
                                <option value="EUR" {{ old('currency') === 'EUR' ? 'selected' : '' }}>EUR (€)</option>
                                <option value="GBP" {{ old('currency') === 'GBP' ? 'selected' : '' }}>GBP (£)</option>
                                <option value="AED" {{ old('currency') === 'AED' ? 'selected' : '' }}>AED (AED)</option>
                                <option value="SAR" {{ old('currency') === 'SAR' ? 'selected' : '' }}>SAR (SAR)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">City</label>
                            <input type="text" name="city" class="form-control" placeholder="e.g. Lahore, Karachi, Islamabad" value="{{ old('city') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Province / State</label>
                            <input type="text" name="province" class="form-control" placeholder="e.g. Punjab, Sindh" value="{{ old('province') }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Physical Address</label>
                            <input type="text" name="address" class="form-control" placeholder="Street, Plaza, or Area" value="{{ old('address') }}">
                        </div>
                    </div>

                    <!-- SECTION 2: OWNER ACCOUNT -->
                    <div class="d-flex align-items-center gap-2 mb-3 text-success fw-bold text-uppercase small">
                        <i class="bi bi-person-fill-gear"></i>
                        <span>2. Business Owner User Account</span>
                        <hr class="flex-grow-1 my-0 text-muted">
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Owner Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="owner_name" class="form-control" placeholder="e.g. Muhammad Ahmed" required value="{{ old('owner_name') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Owner Email (Login ID) <span class="text-danger">*</span></label>
                            <input type="email" name="owner_email" class="form-control" placeholder="owner@gmail.com" required value="{{ old('owner_email') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Owner Phone</label>
                            <input type="text" name="owner_phone" class="form-control" placeholder="e.g. +92 321 7654321" value="{{ old('owner_phone') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Initial Password <span class="text-danger">*</span></label>
                            <input type="password" name="owner_password" class="form-control" placeholder="Minimum 6 characters" required minlength="6">
                        </div>
                    </div>

                    <!-- SECTION 3: SUBSCRIPTION PLAN -->
                    <div class="d-flex align-items-center gap-2 mb-3 text-dark fw-bold text-uppercase small">
                        <i class="bi bi-credit-card-2-front"></i>
                        <span>3. Subscription Plan & Initial State</span>
                        <hr class="flex-grow-1 my-0 text-muted">
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Select Plan <span class="text-danger">*</span></label>
                            <select name="plan_id" class="form-select" required>
                                @foreach($plans as $plan)
                                    <option value="{{ $plan->id }}" {{ old('plan_id') == $plan->id ? 'selected' : '' }}>
                                        {{ $plan->name }} (${{ number_format($plan->price_monthly, 2) }}/month)
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Initial Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-select" required>
                                <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active (Full Access Immediately)</option>
                                <option value="trial" {{ old('status') === 'trial' ? 'selected' : '' }}>14-Day Free Trial</option>
                                <option value="suspended" {{ old('status') === 'suspended' ? 'selected' : '' }}>Suspended (Hold Access)</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-check-lg me-1"></i>Create Business & Owner
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
