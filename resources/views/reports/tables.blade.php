@extends('layouts.app')

@section('title', 'Table Turnover & Revenue Report')

@section('content')
<div class="row g-4 mb-4">
    <div class="col-12 d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <h4 class="mb-1 fw-bold text-body-emphasis">Table Turnover & Occupancy Analytics</h4>
            <p class="text-muted mb-0 small">Dine-in table sales performance, turnover rates, and average spend</p>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
                <i class="bi bi-printer"></i> Print Report
            </button>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Table Status Summary -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-header bg-transparent border-0 pt-3 px-3 pb-0">
                <h6 class="fw-bold mb-0">Current Floor Status</h6>
            </div>
            <div class="card-body p-3">
                <div class="row g-2">
                    @forelse($tables as $t)
                    <div class="col-6">
                        <div class="p-2 rounded-2 border {{ $t->is_occupied ? 'bg-danger-subtle border-danger-subtle text-danger' : 'bg-light border-secondary-subtle text-body' }}">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold small">{{ $t->name }}</span>
                                <span class="badge {{ $t->is_occupied ? 'bg-danger text-white' : 'bg-secondary text-white' }} small" style="font-size: 0.65rem;">
                                    {{ $t->is_occupied ? 'Occupied' : 'Vacant' }}
                                </span>
                            </div>
                            <div class="small opacity-75 mt-1" style="font-size: 0.75rem;">Cap: {{ $t->capacity }} seats</div>
                        </div>
                    </div>
                    @empty
                    <div class="col-12 text-center text-muted py-3">No tables registered.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Revenue by Table -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-3 h-100">
            <div class="card-header bg-transparent border-0 pt-3 px-3 pb-0">
                <h6 class="fw-bold mb-0">Historical Table Revenue & Order Volume</h6>
            </div>
            <div class="card-body p-0 mt-2">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Table</th>
                                <th class="text-center">Orders Served</th>
                                <th class="text-end">Avg. Ticket</th>
                                <th class="text-end pe-3">Total Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tableStats as $stat)
                            <tr>
                                <td class="ps-3 fw-bold">{{ $stat->table_name ?? 'Quick Dine' }}</td>
                                <td class="text-center"><span class="badge bg-primary-subtle text-primary">{{ $stat->total_orders }}</span></td>
                                <td class="text-end text-muted">Rs. {{ number_format($stat->avg_bill, 2) }}</td>
                                <td class="text-end pe-3 fw-bold text-success">Rs. {{ number_format($stat->total_revenue, 2) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">No completed dine-in orders recorded yet.</td>
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
