@extends('layouts.app')

@section('title', 'SaaS Super Admin - Platform Overview')

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Top Header -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1">
                <i class="ph-duotone ph-buildings text-primary me-2"></i>SaaS Platform Administration
            </h1>
            <p class="text-muted mb-0">Multi-tenant overview, live subscription revenue, and food point metrics.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('saas.tenants.index') }}" class="btn btn-outline-primary d-inline-flex align-items-center gap-2">
                <i class="ph-duotone ph-storefront"></i>
                <span>Manage Tenants</span>
            </a>
            <a href="{{ route('saas.plans.index') }}" class="btn btn-primary d-inline-flex align-items-center gap-2">
                <i class="ph-duotone ph-credit-card"></i>
                <span>Plans & Pricing</span>
            </a>
        </div>
    </div>

    <!-- Stats Cards Row -->
    <div class="row g-3 mb-4">
        <!-- Total Tenants -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase">Total Food Points</span>
                    <div class="rounded-3 p-2 bg-primary bg-opacity-10 text-primary">
                        <i class="ph-duotone ph-storefront fs-4"></i>
                    </div>
                </div>
                <h2 class="fw-bold mb-1 text-dark">{{ $totalTenants }}</h2>
                <div class="d-flex align-items-center gap-2 small">
                    <span class="badge bg-success bg-opacity-10 text-success fw-semibold">{{ $activeTenants }} Active</span>
                    <span class="badge bg-warning bg-opacity-10 text-warning fw-semibold">{{ $trialTenants }} Trial</span>
                    @if($suspendedTenants > 0)
                        <span class="badge bg-danger bg-opacity-10 text-danger fw-semibold">{{ $suspendedTenants }} Suspended</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Monthly Recurring Revenue (MRR) -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase">Estimated MRR</span>
                    <div class="rounded-3 p-2 bg-success bg-opacity-10 text-success">
                        <i class="ph-duotone ph-currency-circle-dollar fs-4"></i>
                    </div>
                </div>
                <h2 class="fw-bold mb-1 text-success">${{ number_format($mrr, 2) }}</h2>
                <div class="small text-muted">
                    <span>{{ $activeSubscriptions }} active paid subscriptions</span>
                </div>
            </div>
        </div>

        <!-- Platform Total Orders -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase">Platform Orders</span>
                    <div class="rounded-3 p-2 bg-info bg-opacity-10 text-info">
                        <i class="ph-duotone ph-receipt fs-4"></i>
                    </div>
                </div>
                <h2 class="fw-bold mb-1 text-dark">{{ number_format($totalOrders) }}</h2>
                <div class="small text-muted">
                    <span>Cumulative across all restaurants</span>
                </div>
            </div>
        </div>

        <!-- Platform Total Gross GMV -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100 p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase">Total Sales (GMV)</span>
                    <div class="rounded-3 p-2 bg-warning bg-opacity-10 text-warning">
                        <i class="ph-duotone ph-chart-line-up fs-4"></i>
                    </div>
                </div>
                <h2 class="fw-bold mb-1 text-dark">${{ number_format($totalPlatformSales, 2) }}</h2>
                <div class="small text-muted">
                    <span>Processed through POS terminal</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Tables -->
    <div class="row g-4">
        <!-- Recent Registered Tenants -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100">
                <div class="card-header bg-white border-0 py-3 px-4 d-flex align-items-center justify-content-between">
                    <h5 class="fw-bold mb-0 text-dark">
                        <i class="ph-duotone ph-storefront me-2 text-primary"></i>Recent Tenants
                    </h5>
                    <a href="{{ route('saas.tenants.index') }}" class="btn btn-sm btn-link text-decoration-none">View All &rarr;</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 px-4">
                        <thead class="table-light text-muted small text-uppercase">
                            <tr>
                                <th class="ps-4">Food Point / Restaurant</th>
                                <th>Plan</th>
                                <th>Status</th>
                                <th>Joined</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentTenants as $tenant)
                            @php
                                $sub = $tenant->subscriptions->first();
                            @endphp
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="rounded-circle bg-primary bg-opacity-10 text-primary fw-bold d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                                            {{ strtoupper(substr($tenant->name, 0, 2)) }}
                                        </div>
                                        <div>
                                            <a href="{{ route('saas.tenants.show', $tenant) }}" class="fw-semibold text-dark text-decoration-none d-block">
                                                {{ $tenant->name }}
                                            </a>
                                            <span class="text-muted small">{{ $tenant->email ?? $tenant->slug }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        {{ $sub?->plan?->name ?? 'None' }}
                                    </span>
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
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('saas.tenants.show', $tenant) }}" class="btn btn-outline-secondary" title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('saas.tenants.impersonate', $tenant) }}" class="btn btn-outline-warning" title="Impersonate (One-Click Login)">
                                            <i class="ph-duotone ph-user-switch"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">No tenants registered yet.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Recent Subscriptions -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100">
                <div class="card-header bg-white border-0 py-3 px-4 d-flex align-items-center justify-content-between">
                    <h5 class="fw-bold mb-0 text-dark">
                        <i class="ph-duotone ph-credit-card me-2 text-success"></i>Recent Subscriptions
                    </h5>
                    <a href="{{ route('saas.plans.index') }}" class="btn btn-sm btn-link text-decoration-none">Manage Plans &rarr;</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 px-4">
                        <thead class="table-light text-muted small text-uppercase">
                            <tr>
                                <th class="ps-4">Tenant</th>
                                <th>Plan</th>
                                <th>Status</th>
                                <th class="text-end pe-4">Expires</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentSubscriptions as $subscription)
                            <tr>
                                <td class="ps-4">
                                    <span class="fw-semibold text-dark">{{ $subscription->tenant?->name ?? 'Unknown' }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-primary bg-opacity-10 text-primary">
                                        {{ $subscription->plan?->name ?? 'Standard' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge {{ $subscription->status === 'active' ? 'badge-soft-success' : 'badge-soft-warning' }}">
                                        {{ ucfirst($subscription->status) }}
                                    </span>
                                </td>
                                <td class="text-end pe-4 text-muted small">
                                    {{ $subscription->ends_at ? $subscription->ends_at->format('M d, Y') : 'Lifetime' }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">No subscription records yet.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
