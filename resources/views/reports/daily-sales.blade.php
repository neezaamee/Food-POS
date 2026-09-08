@extends('layouts.app')

@section('title', 'Day-Wise Sales Report - Food Point POS')

@section('content')
<div class="row g-4 mb-4">
    <div class="col-12 d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <h4 class="mb-1 fw-bold text-body-emphasis"><i class="ph-duotone ph-calendar-check me-2 text-primary"></i> Day-Wise Sales Report</h4>
            <p class="text-muted mb-0 small">Consolidates all shifts of each business day (Lunch + Dinner) into unified daily sales.</p>
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
            <a class="nav-link active fw-semibold py-1 px-3" href="{{ route('reports.daily-sales') }}">
                <i class="bi bi-calendar-day me-1"></i> Day-Wise Sales (Consolidated)
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link fw-semibold py-1 px-3 text-muted" href="{{ route('reports.shift-sales') }}">
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
    <div class="col-sm-6 col-lg-4">
        <div class="card border shadow-sm h-100">
            <div class="card-body p-3 d-flex align-items-center gap-3">
                <div class="rounded-3 bg-primary-subtle text-primary p-3 fs-3">
                    <i class="ph-duotone ph-currency-circle-dollar"></i>
                </div>
                <div>
                    <div class="text-muted small">Total Period Gross Sales</div>
                    <div class="fs-4 fw-bold text-primary">Rs. {{ number_format($periodGrossSales, 2) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-4">
        <div class="card border shadow-sm h-100">
            <div class="card-body p-3 d-flex align-items-center gap-3">
                <div class="rounded-3 bg-success-subtle text-success p-3 fs-3">
                    <i class="ph-duotone ph-receipt"></i>
                </div>
                <div>
                    <div class="text-muted small">Total Completed Orders</div>
                    <div class="fs-4 fw-bold text-success">{{ number_format($periodOrdersCount) }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-4">
        <div class="card border shadow-sm h-100">
            <div class="card-body p-3 d-flex align-items-center gap-3">
                <div class="rounded-3 bg-info-subtle text-info p-3 fs-3">
                    <i class="ph-duotone ph-wallet"></i>
                </div>
                <div>
                    <div class="text-muted small">Total Net Collected</div>
                    <div class="fs-4 fw-bold text-info">Rs. {{ number_format($periodNetCollected, 2) }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters Card -->
<div class="card border shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('reports.daily-sales') }}" class="row g-2 align-items-end">
            <div class="col-md-4 col-sm-6">
                <label class="form-label small text-muted mb-1">From Date</label>
                <input type="date" name="from_date" value="{{ $fromDate }}" class="form-control form-control-sm">
            </div>
            <div class="col-md-4 col-sm-6">
                <label class="form-label small text-muted mb-1">To Date</label>
                <input type="date" name="to_date" value="{{ $toDate }}" class="form-control form-control-sm">
            </div>
            <div class="col-md-4 col-sm-12 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                    <i class="bi bi-funnel me-1"></i> Apply Filter
                </button>
                <a href="{{ route('reports.daily-sales') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Daily Sales Table -->
<div class="card border shadow-sm">
    <div class="card-header bg-transparent py-3 d-flex align-items-center justify-content-between">
        <h5 class="card-title mb-0 fs-6 fw-bold">Day-by-Day Sales Breakdown</h5>
        <span class="small text-muted">Click on a day to view its constituent shifts</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="min-width: 160px;">Business Date</th>
                        <th style="min-width: 220px;">Shifts Operated (Lunch / Dinner)</th>
                        <th class="text-center">Orders</th>
                        <th class="text-end">Gross Sales</th>
                        <th class="text-end">Discounts</th>
                        <th class="text-end">Tax</th>
                        <th class="text-end">Net Sales</th>
                        <th style="min-width: 170px;">Payment Split</th>
                        <th class="text-center">Shifts</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($dailyRecords as $day)
                        @php
                            $rowId = 'dayDetails_' . str_replace('-', '_', $day->sale_date);
                        @endphp
                        <tr>
                            <td>
                                <div class="fw-bold text-heading fs-6">{{ \Carbon\Carbon::parse($day->sale_date)->format('d M Y') }}</div>
                                <div class="small text-muted">{{ \Carbon\Carbon::parse($day->sale_date)->format('l') }}</div>
                            </td>
                            <td>
                                @if ($day->shifts->isNotEmpty())
                                    <div class="d-flex flex-wrap gap-1">
                                        @foreach ($day->shifts as $s)
                                            <span class="badge {{ $s->status === 'open' ? 'bg-success' : 'bg-secondary' }} px-2 py-1" style="font-size: 0.75rem;">
                                                Shift #{{ $s->id }} ({{ $s->user?->name ?? 'Cashier' }})
                                            </span>
                                        @endforeach
                                    </div>
                                    <div class="small text-muted mt-1">{{ $day->shifts->count() }} shift(s) active</div>
                                @else
                                    <span class="small text-muted fst-italic">No shifts recorded</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border fw-bold">{{ $day->total_orders }}</span>
                            </td>
                            <td class="text-end fw-bold text-heading">
                                Rs. {{ number_format($day->gross_sales, 2) }}
                            </td>
                            <td class="text-end text-danger small">
                                - Rs. {{ number_format($day->total_discount, 2) }}
                            </td>
                            <td class="text-end text-muted small">
                                + Rs. {{ number_format($day->total_tax, 2) }}
                            </td>
                            <td class="text-end fw-bold text-success fs-6">
                                Rs. {{ number_format($day->net_collected, 2) }}
                            </td>
                            <td>
                                <div class="small">
                                    <span class="text-success"><i class="bi bi-cash me-1"></i>Rs. {{ number_format($day->cash_sales, 0) }}</span>
                                    <span class="text-muted mx-1">•</span>
                                    <span class="text-primary"><i class="bi bi-credit-card me-1"></i>Rs. {{ number_format($day->digital_sales, 0) }}</span>
                                </div>
                            </td>
                            <td class="text-center">
                                @if ($day->shifts->isNotEmpty())
                                    <button class="btn btn-outline-primary btn-sm py-0 px-2" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $rowId }}" aria-expanded="false" style="font-size: 0.75rem;">
                                        <i class="bi bi-chevron-down me-1"></i> Breakdown
                                    </button>
                                @else
                                    <span class="text-muted small">-</span>
                                @endif
                            </td>
                        </tr>

                        <!-- COLLAPSIBLE SHIFT BREAKDOWN ROW -->
                        @if ($day->shifts->isNotEmpty())
                            <tr class="collapse bg-light-subtle" id="{{ $rowId }}">
                                <td colspan="9" class="p-3">
                                    <div class="border rounded p-3 bg-white shadow-sm">
                                        <h6 class="fw-bold mb-2 text-dark">
                                            <i class="ph-duotone ph-vault me-1 text-primary"></i> Shifts Breakdown for {{ \Carbon\Carbon::parse($day->sale_date)->format('d F Y') }}
                                        </h6>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered align-middle mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Shift #</th>
                                                        <th>Cashier</th>
                                                        <th>Opened</th>
                                                        <th>Closed</th>
                                                        <th class="text-end">Opening Float</th>
                                                        <th class="text-end">Cash Sales</th>
                                                        <th class="text-end">Expected Cash</th>
                                                        <th class="text-end">Counted Cash</th>
                                                        <th class="text-end">Discrepancy</th>
                                                        <th>Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($day->shifts as $shift)
                                                        <tr>
                                                            <td class="fw-bold">Shift #{{ $shift->id }}</td>
                                                            <td>{{ $shift->user?->name ?? 'N/A' }}</td>
                                                            <td class="small">{{ $shift->opened_at->format('h:i A') }}</td>
                                                            <td class="small">{{ $shift->closed_at ? $shift->closed_at->format('h:i A') : 'Still Open' }}</td>
                                                            <td class="text-end small">Rs. {{ number_format($shift->opening_cash) }}</td>
                                                            <td class="text-end fw-bold text-success small">Rs. {{ number_format($shift->cash_sales) }}</td>
                                                            <td class="text-end small">Rs. {{ number_format($shift->expected_cash) }}</td>
                                                            <td class="text-end small">Rs. {{ number_format($shift->actual_cash) }}</td>
                                                            <td class="text-end small {{ $shift->difference < 0 ? 'text-danger fw-bold' : ($shift->difference > 0 ? 'text-success fw-bold' : 'text-muted') }}">
                                                                {{ $shift->difference != 0 ? 'Rs. ' . number_format($shift->difference) : 'Balanced' }}
                                                            </td>
                                                            <td>
                                                                <span class="badge {{ $shift->status === 'open' ? 'bg-success' : 'bg-secondary' }}">
                                                                    {{ ucfirst($shift->status) }}
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="ph-duotone ph-calendar-blank fs-1 mb-2 opacity-50 d-block"></i>
                                <h5>No sales records found for this period</h5>
                                <p class="small mb-0">Try changing the date filter above.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($dailyRecords->hasPages())
            <div class="p-3 border-top">
                {{ $dailyRecords->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
