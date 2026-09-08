@extends('layouts.app')

@section('title', 'Shift-Wise Sales Report - Food Point POS')

@section('content')
<div class="row g-4 mb-4">
    <div class="col-12 d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <h4 class="mb-1 fw-bold text-body-emphasis"><i class="ph-duotone ph-vault me-2 text-primary"></i> Shift-Wise Sales Report</h4>
            <p class="text-muted mb-0 small">Performance, collections, and cash drawer reconciliations for each individual cashier shift.</p>
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
            <a class="nav-link active fw-semibold py-1 px-3" href="{{ route('reports.shift-sales') }}">
                <i class="ph-duotone ph-vault me-1"></i> Shift-Wise Sales
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link fw-semibold py-1 px-3 text-muted" href="{{ route('reports.sales') }}">
                <i class="bi bi-list-check me-1"></i> Itemized Orders List
            </a>
        </li>
    </ul>
</div>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-6">
        <div class="card border shadow-sm h-100">
            <div class="card-body p-3 d-flex align-items-center gap-3">
                <div class="rounded-3 bg-primary-subtle text-primary p-3 fs-3">
                    <i class="ph-duotone ph-cash-register"></i>
                </div>
                <div>
                    <div class="text-muted small">Total Closed Shifts Cash Sales</div>
                    <div class="fs-4 fw-bold text-primary">Rs. {{ number_format($totalShiftSales, 2) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-6">
        <div class="card border shadow-sm h-100">
            <div class="card-body p-3 d-flex align-items-center gap-3">
                <div class="rounded-3 {{ $totalDifference < 0 ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success' }} p-3 fs-3">
                    <i class="ph-duotone ph-scales"></i>
                </div>
                <div>
                    <div class="text-muted small">Net Drawer Discrepancy (Over / Short)</div>
                    <div class="fs-4 fw-bold {{ $totalDifference < 0 ? 'text-danger' : ($totalDifference > 0 ? 'text-success' : 'text-muted') }}">
                        {{ $totalDifference != 0 ? 'Rs. ' . number_format($totalDifference, 2) : 'Rs. 0.00 (Balanced)' }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters Card -->
<div class="card border shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('reports.shift-sales') }}" class="row g-2 align-items-end">
            <div class="col-md-3 col-sm-6">
                <label class="form-label small text-muted mb-1">From Date</label>
                <input type="date" name="from_date" value="{{ request('from_date') }}" class="form-control form-control-sm">
            </div>
            <div class="col-md-3 col-sm-6">
                <label class="form-label small text-muted mb-1">To Date</label>
                <input type="date" name="to_date" value="{{ request('to_date') }}" class="form-control form-control-sm">
            </div>
            <div class="col-md-2 col-sm-6">
                <label class="form-label small text-muted mb-1">Cashier</label>
                <select name="user_id" class="form-select form-select-sm">
                    <option value="">All Cashiers</option>
                    @foreach ($cashiers as $c)
                        <option value="{{ $c->id }}" {{ request('user_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 col-sm-6">
                <label class="form-label small text-muted mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="open" {{ request('status') === 'open' ? 'selected' : '' }}>Open Shifts</option>
                    <option value="closed" {{ request('status') === 'closed' ? 'selected' : '' }}>Closed Shifts</option>
                </select>
            </div>
            <div class="col-md-2 col-sm-12 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1">Filter</button>
                <a href="{{ route('reports.shift-sales') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Shift Table -->
<div class="card border shadow-sm">
    <div class="card-header bg-transparent py-3">
        <h5 class="card-title mb-0 fs-6 fw-bold">Individual Shift Reconciliation & Sales</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Shift #</th>
                        <th>Cashier</th>
                        <th>Operating Hours</th>
                        <th class="text-end">Opening Float</th>
                        <th class="text-end">Cash Sales</th>
                        <th class="text-end">Expected Cash</th>
                        <th class="text-end">Counted Cash</th>
                        <th class="text-end">Discrepancy</th>
                        <th class="text-center">Orders</th>
                        <th>Status</th>
                        <th class="text-end">Details</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($shifts as $shift)
                        <tr>
                            <td>
                                <strong class="text-heading">Shift #{{ $shift->id }}</strong>
                            </td>
                            <td>
                                <div class="fw-semibold">{{ $shift->user?->name ?? 'System Cashier' }}</div>
                            </td>
                            <td class="small">
                                <div><i class="bi bi-clock me-1 text-muted"></i>{{ $shift->opened_at->format('d M Y, h:i A') }}</div>
                                @if ($shift->closed_at)
                                    <div class="text-muted"><i class="bi bi-check-circle me-1 text-success"></i>Closed: {{ $shift->closed_at->format('h:i A') }}</div>
                                @else
                                    <div class="text-warning fw-semibold"><i class="bi bi-broadcast me-1"></i>Currently Open</div>
                                @endif
                            </td>
                            <td class="text-end small">
                                Rs. {{ number_format($shift->opening_cash, 2) }}
                            </td>
                            <td class="text-end fw-bold text-success">
                                Rs. {{ number_format($shift->cash_sales, 2) }}
                            </td>
                            <td class="text-end small">
                                Rs. {{ number_format($shift->expected_cash, 2) }}
                            </td>
                            <td class="text-end small fw-semibold">
                                {{ $shift->actual_cash ? 'Rs. ' . number_format($shift->actual_cash, 2) : '-' }}
                            </td>
                            <td class="text-end small {{ $shift->difference < 0 ? 'text-danger fw-bold' : ($shift->difference > 0 ? 'text-success fw-bold' : 'text-muted') }}">
                                @if (!is_null($shift->difference))
                                    {{ $shift->difference != 0 ? 'Rs. ' . number_format($shift->difference, 2) : 'Balanced' }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border fw-bold">{{ $shift->orders->count() }}</span>
                            </td>
                            <td>
                                <span class="badge {{ $shift->status === 'open' ? 'bg-success' : 'bg-secondary' }}">
                                    {{ ucfirst($shift->status) }}
                                </span>
                            </td>
                            <td class="text-end">
                                <button type="button" class="btn btn-outline-primary btn-sm py-0 px-2" data-bs-toggle="modal" data-bs-target="#shiftOrdersModal{{ $shift->id }}" style="font-size: 0.75rem;">
                                    <i class="bi bi-eye me-1"></i> Orders
                                </button>
                            </td>
                        </tr>

                        <!-- SHIFT ORDERS MODAL -->
                        <div class="modal fade" id="shiftOrdersModal{{ $shift->id }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-centered">
                                <div class="modal-content border-0 shadow">
                                    <div class="modal-header bg-primary text-white py-2 px-3">
                                        <h6 class="modal-title fw-bold mb-0">
                                            <i class="ph-duotone ph-vault me-2"></i> Shift #{{ $shift->id }} Orders ({{ $shift->user?->name ?? 'Cashier' }})
                                        </h6>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body p-3">
                                        <div class="d-flex justify-content-between align-items-center mb-3 p-2 bg-light rounded small">
                                            <span>Opened: <strong>{{ $shift->opened_at->format('d M Y, h:i A') }}</strong></span>
                                            <span>Closed: <strong>{{ $shift->closed_at ? $shift->closed_at->format('d M Y, h:i A') : 'Active' }}</strong></span>
                                            <span>Total Shift Orders: <strong>{{ $shift->orders->count() }}</strong></span>
                                        </div>

                                        <div class="table-responsive" style="max-height: 380px;">
                                            <table class="table table-sm align-middle mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Order #</th>
                                                        <th>Channel</th>
                                                        <th>Customer / Table</th>
                                                        <th class="text-end">Total</th>
                                                        <th>Payment</th>
                                                        <th>Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse ($shift->orders as $ord)
                                                        <tr>
                                                            <td class="fw-bold font-monospace">{{ $ord->order_number }}</td>
                                                            <td>
                                                                <span class="badge bg-dark" style="font-size: 0.7rem;">{{ $ord->order_type }}</span>
                                                            </td>
                                                            <td class="small">{{ $ord->customer_name ?: ($ord->table_name ?: 'Walk-in') }}</td>
                                                            <td class="text-end fw-bold small">Rs. {{ number_format($ord->grand_total, 2) }}</td>
                                                            <td class="small">
                                                                <span class="badge {{ $ord->payment_status === 'paid' ? 'bg-success' : 'bg-warning text-dark' }}">
                                                                    {{ ucfirst($ord->payment_status) }}
                                                                </span>
                                                            </td>
                                                            <td class="small">{{ ucfirst($ord->order_status) }}</td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="6" class="text-center py-4 text-muted small">
                                                                No orders were punched during this shift.
                                                            </td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="modal-footer py-2 px-3 bg-light">
                                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center py-5 text-muted">
                                <i class="ph-duotone ph-vault fs-1 mb-2 opacity-50 d-block"></i>
                                <h5>No shifts found for this selection</h5>
                                <p class="small mb-0">Try changing your filters above.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($shifts->hasPages())
            <div class="p-3 border-top">
                {{ $shifts->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
