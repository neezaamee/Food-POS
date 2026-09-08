@extends('layouts.app')

@section('title', 'Sales Summary Report')

@section('content')
<div class="row g-4 mb-4">
    <div class="col-12 d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <h4 class="mb-1 fw-bold text-body-emphasis">Sales Summary Report</h4>
            <p class="text-muted mb-0 small">Filtered sales, revenue, discounts, and order analytics</p>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
                <i class="bi bi-printer"></i> Print Report
            </button>
        </div>
    </div>
</div>

<!-- Sales Navigation Pills -->
<div class="mb-4">
    <ul class="nav nav-pills bg-light p-1 rounded-3 d-inline-flex border">
        <li class="nav-item">
            <a class="nav-link fw-semibold py-1 px-3 text-muted" href="{{ route('reports.daily-sales') }}">
                <i class="bi bi-calendar-day me-1"></i> Day-Wise Sales (Consolidated)
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link fw-semibold py-1 px-3 text-muted" href="{{ route('reports.shift-sales') }}">
                <i class="ph-duotone ph-vault me-1"></i> Shift-Wise Sales
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link active fw-semibold py-1 px-3" href="{{ route('reports.sales') }}">
                <i class="bi bi-list-check me-1"></i> Itemized Orders List
            </a>
        </li>
    </ul>
</div>

<!-- Filters Card -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('reports.sales') }}" class="row g-2 align-items-end">
            <div class="col-md-2 col-sm-6">
                <label class="form-label small text-muted mb-1">From Date</label>
                <input type="date" name="from_date" value="{{ request('from_date') }}" class="form-control form-control-sm">
            </div>
            <div class="col-md-2 col-sm-6">
                <label class="form-label small text-muted mb-1">To Date</label>
                <input type="date" name="to_date" value="{{ request('to_date') }}" class="form-control form-control-sm">
            </div>
            <div class="col-md-3 col-sm-6">
                <label class="form-label small text-muted mb-1">Order Type</label>
                <select name="order_type" class="form-select form-select-sm">
                    <option value="">All Channels</option>
                    <option value="TAKEAWAY" {{ request('order_type') == 'TAKEAWAY' ? 'selected' : '' }}>Takeaway</option>
                    <option value="DINE_IN" {{ request('order_type') == 'DINE_IN' ? 'selected' : '' }}>Dine-In</option>
                    <option value="DELIVERY" {{ request('order_type') == 'DELIVERY' ? 'selected' : '' }}>Delivery</option>
                </select>
            </div>
            <div class="col-md-3 col-sm-6">
                <label class="form-label small text-muted mb-1">Cashier / Staff</label>
                <select name="user_id" class="form-select form-select-sm">
                    <option value="">All Cashiers</option>
                    @foreach($cashiers as $c)
                        <option value="{{ $c->id }}" {{ request('user_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 col-sm-12 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm flex-fill">Filter</button>
                <a href="{{ route('reports.sales') }}" class="btn btn-light btn-sm">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Metrics Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-primary text-white">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="small opacity-75 fw-medium">Gross Completed Sales</div>
                    <div class="fs-4 fw-bold mt-1">Rs. {{ number_format($totalSales, 2) }}</div>
                </div>
                <div class="bg-white bg-opacity-25 rounded-circle p-3 d-flex align-items-center justify-content-center">
                    <i class="bi bi-wallet2 fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-danger bg-opacity-10 border border-danger-subtle">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="small text-danger fw-medium">Discounts Awarded</div>
                    <div class="fs-4 fw-bold text-danger mt-1">Rs. {{ number_format($totalDiscount, 2) }}</div>
                </div>
                <div class="bg-danger bg-opacity-25 text-danger rounded-circle p-3 d-flex align-items-center justify-content-center">
                    <i class="bi bi-tag fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-info bg-opacity-10 border border-info-subtle">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="small text-info-emphasis fw-medium">Taxes Collected</div>
                    <div class="fs-4 fw-bold text-info-emphasis mt-1">Rs. {{ number_format($totalTax, 2) }}</div>
                </div>
                <div class="bg-info bg-opacity-25 text-info-emphasis rounded-circle p-3 d-flex align-items-center justify-content-center">
                    <i class="bi bi-receipt-cutoff fs-4"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Sales Orders Table -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-header bg-transparent border-0 pt-3 px-3 pb-0 d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0">Completed Orders ({{ $orders->total() }})</h6>
    </div>
    <div class="card-body p-0 mt-2">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Order #</th>
                        <th>Type</th>
                        <th>Customer / Detail</th>
                        <th>Cashier</th>
                        <th>Date & Time</th>
                        <th>Discount</th>
                        <th>Tax</th>
                        <th class="text-end pe-3">Grand Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $o)
                    <tr>
                        <td class="ps-3 fw-bold">
                            <a href="{{ route('orders.show', $o->id) }}" class="text-decoration-none">
                                {{ $o->order_number }}
                            </a>
                        </td>
                        <td>
                            <span class="badge {{ $o->order_type == 'TAKEAWAY' ? 'bg-primary-subtle text-primary border border-primary-subtle' : ($o->order_type == 'DINE_IN' ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-warning-subtle text-warning border border-warning-subtle') }}">
                                {{ $o->order_type }}
                            </span>
                        </td>
                        <td>
                            @if($o->customer)
                                <div class="fw-medium text-body">{{ $o->customer->name }}</div>
                            @elseif($o->table)
                                <div class="fw-medium text-body"><i class="bi bi-grid-fill me-1 text-muted"></i>{{ $o->table->name }}</div>
                            @else
                                <span class="text-muted">Walk-in Customer</span>
                            @endif
                        </td>
                        <td class="text-muted small">{{ $o->cashier->name ?? 'System' }}</td>
                        <td class="text-muted small">{{ $o->finalized_at ? $o->finalized_at->format('M d, Y h:i A') : $o->created_at->format('M d, Y h:i A') }}</td>
                        <td class="text-danger small">Rs. {{ number_format($o->discount_amount, 2) }}</td>
                        <td class="text-muted small">Rs. {{ number_format($o->tax_amount, 2) }}</td>
                        <td class="text-end pe-3 fw-bold text-body-emphasis">Rs. {{ number_format($o->grand_total, 2) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">No sales orders found matching selected criteria.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($orders->hasPages())
    <div class="card-footer bg-transparent border-0 px-3 py-2">
        {{ $orders->links() }}
    </div>
    @endif
</div>
@endsection
