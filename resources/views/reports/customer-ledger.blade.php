@extends('layouts.app')

@section('title', 'Customer Ledger Statement')

@section('content')
<div class="row g-4 mb-4">
    <div class="col-12 d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <h4 class="mb-1 fw-bold text-body-emphasis">Customer Statement & Account Ledger</h4>
            <p class="text-muted mb-0 small">Account receivables, purchase records, and credit statements</p>
        </div>
        <div class="d-flex gap-2">
            @if($selectedCustomer)
            <button onclick="window.print()" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
                <i class="bi bi-printer"></i> Print Statement
            </button>
            @endif
        </div>
    </div>
</div>

<!-- Customer Selection -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('reports.customer-ledger') }}" class="row g-2 align-items-end">
            <div class="col-md-9">
                <label class="form-label small text-muted mb-1">Select Customer Account</label>
                <select name="customer_id" class="form-select form-select-sm" required>
                    <option value="">-- Choose a Customer --</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}" {{ request('customer_id') == $c->id ? 'selected' : '' }}>
                            {{ $c->name }} ({{ $c->phone }}) — Bal: Rs. {{ number_format($c->balance, 2) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm flex-fill">Load Ledger</button>
                <a href="{{ route('reports.customer-ledger') }}" class="btn btn-light btn-sm">Clear</a>
            </div>
        </form>
    </div>
</div>

@if($selectedCustomer)
<!-- Customer Snapshot Card -->
<div class="card border-0 shadow-sm rounded-3 mb-4 bg-light">
    <div class="card-body p-4">
        <div class="row g-3 align-items-center">
            <div class="col-md-6">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-3 fs-3">
                        <i class="bi bi-person-badge"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-1">{{ $selectedCustomer->name }}</h5>
                        <div class="text-muted small"><i class="bi bi-telephone me-1"></i>{{ $selectedCustomer->phone }} | <i class="bi bi-geo-alt me-1"></i>{{ $selectedCustomer->address ?? 'No physical address' }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 text-md-end">
                <div class="d-inline-block text-start p-3 bg-white rounded-3 shadow-sm border">
                    <div class="small text-muted">Current Outstanding Balance</div>
                    <div class="fs-4 fw-bold {{ $selectedCustomer->balance > 0 ? 'text-danger' : 'text-success' }}">
                        Rs. {{ number_format($selectedCustomer->balance, 2) }}
                    </div>
                    <div class="small text-muted">Credit Limit: Rs. {{ number_format($selectedCustomer->credit_limit, 2) }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Order Invoices -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-header bg-transparent border-0 pt-3 px-3 pb-0">
        <h6 class="fw-bold mb-0">Transaction & Invoice History</h6>
    </div>
    <div class="card-body p-0 mt-2">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Invoice #</th>
                        <th>Type</th>
                        <th>Date</th>
                        <th>Total Bill</th>
                        <th>Paid Amount</th>
                        <th>Due Amount</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customerOrders as $ord)
                    <tr>
                        <td class="ps-3 fw-bold">
                            <a href="{{ route('orders.show', $ord->id) }}" class="text-decoration-none">
                                {{ $ord->order_number }}
                            </a>
                        </td>
                        <td>
                            <span class="badge bg-secondary-subtle text-secondary">{{ $ord->order_type }}</span>
                        </td>
                        <td class="text-muted small">{{ $ord->created_at->format('M d, Y h:i A') }}</td>
                        <td class="fw-bold">Rs. {{ number_format($ord->grand_total, 2) }}</td>
                        <td class="text-success fw-medium">Rs. {{ number_format($ord->paid_amount, 2) }}</td>
                        <td class="{{ $ord->due_amount > 0 ? 'text-danger fw-bold' : 'text-muted' }}">Rs. {{ number_format($ord->due_amount, 2) }}</td>
                        <td class="text-center">
                            @if($ord->payment_status === 'paid')
                                <span class="badge bg-success-subtle text-success border border-success-subtle">Paid</span>
                            @elseif($ord->payment_status === 'partial')
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Partial</span>
                            @else
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Due</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">No invoices recorded for this customer.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@else
<div class="card border-0 shadow-sm rounded-3 py-5 text-center text-muted">
    <i class="bi bi-search fs-1 mb-2"></i>
    <h6>Please select a customer above to display their financial statement</h6>
</div>
@endif
@endsection
