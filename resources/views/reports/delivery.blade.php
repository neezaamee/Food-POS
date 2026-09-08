@extends('layouts.app')

@section('title', 'Delivery & Rider Performance Report')

@section('content')
<div class="row g-4 mb-4">
    <div class="col-12 d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <h4 class="mb-1 fw-bold text-body-emphasis">Delivery Performance & Rider Mileage Report</h4>
            <p class="text-muted mb-0 small">Delivery metrics, rider odometer tracking, trips breakdown, and dispatch performance</p>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
                <i class="bi bi-printer"></i> Print Report
            </button>
        </div>
    </div>
</div>

<!-- Filter Bar -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('reports.delivery') }}" class="row g-2 align-items-end">
            <div class="col-md-3 col-sm-6">
                <label class="form-label small text-muted mb-1">From Date</label>
                <input type="date" name="from_date" value="{{ request('from_date') }}" class="form-control form-control-sm">
            </div>
            <div class="col-md-3 col-sm-6">
                <label class="form-label small text-muted mb-1">To Date</label>
                <input type="date" name="to_date" value="{{ request('to_date') }}" class="form-control form-control-sm">
            </div>
            <div class="col-md-3 col-sm-6">
                <label class="form-label small text-muted mb-1">Assigned Rider</label>
                <select name="rider_id" class="form-select form-select-sm">
                    <option value="">All Riders</option>
                    @foreach($riders as $r)
                        <option value="{{ $r->id }}" {{ request('rider_id') == $r->id ? 'selected' : '' }}>{{ $r->name }} ({{ $r->mobile }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 col-sm-6 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm flex-fill">Apply Filter</button>
                <a href="{{ route('reports.delivery') }}" class="btn btn-light btn-sm">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Metrics Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-primary bg-opacity-10 border border-primary-subtle">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="small text-primary fw-medium">Delivered Orders</div>
                    <div class="fs-4 fw-bold text-primary mt-1">{{ number_format($totalDeliveries) }}</div>
                </div>
                <div class="bg-primary bg-opacity-25 text-primary rounded-circle p-3 d-flex align-items-center justify-content-center">
                    <i class="bi bi-bicycle fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-success bg-opacity-10 border border-success-subtle">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="small text-success fw-medium">Total Delivery Fees Collected</div>
                    <div class="fs-4 fw-bold text-success mt-1">Rs. {{ number_format($totalDeliveryCharges, 2) }}</div>
                </div>
                <div class="bg-success bg-opacity-25 text-success rounded-circle p-3 d-flex align-items-center justify-content-center">
                    <i class="bi bi-cash-coin fs-4"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-warning bg-opacity-10 border border-warning-subtle">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="small text-warning-emphasis fw-medium">Total Kilometers Logged</div>
                    <div class="fs-4 fw-bold text-warning-emphasis mt-1">{{ number_format($totalDeliveryKm, 1) }} KM</div>
                </div>
                <div class="bg-warning bg-opacity-25 text-warning-emphasis rounded-circle p-3 d-flex align-items-center justify-content-center">
                    <i class="bi bi-speedometer fs-4"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Rider Mileage & Performance Summary -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-header bg-transparent border-0 pt-3 px-3 pb-0 d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0"><i class="bi bi-person-lines-fill text-primary me-2"></i>Rider Mileage & Performance Summary</h6>
        <span class="badge bg-light text-dark border">{{ count($riderStats) }} Active Riders</span>
    </div>
    <div class="card-body p-0 mt-2">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Rider Name</th>
                        <th>Mobile</th>
                        <th>Vehicle / Number</th>
                        <th class="text-center">Delivered Orders</th>
                        <th class="text-center">Total Mileage</th>
                        <th class="text-center">Avg Distance / Trip</th>
                        <th class="text-end">Delivery Fees</th>
                        <th class="text-end pe-3">Total Order Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($riderStats as $rs)
                    <tr>
                        <td class="ps-3 fw-bold">
                            <i class="bi bi-person-badge text-muted me-1"></i>{{ $rs->rider->name }}
                        </td>
                        <td class="small text-muted">{{ $rs->rider->mobile }}</td>
                        <td>
                            <span class="badge bg-secondary-subtle text-secondary border">
                                {{ $rs->rider->vehicle_type }} ({{ $rs->rider->vehicle_number ?: 'N/A' }})
                            </span>
                        </td>
                        <td class="text-center fw-bold">{{ $rs->trips_count }}</td>
                        <td class="text-center">
                            @if($rs->total_km > 0)
                                <span class="badge bg-warning-subtle text-warning-emphasis fw-bold fs-7">
                                    <i class="bi bi-speedometer me-1"></i>{{ number_format($rs->total_km, 1) }} KM
                                </span>
                            @else
                                <span class="text-muted">0.0 KM</span>
                            @endif
                        </td>
                        <td class="text-center text-muted small">
                            {{ $rs->trips_count > 0 ? number_format($rs->avg_km, 1) . ' KM' : '—' }}
                        </td>
                        <td class="text-end fw-medium text-success">Rs. {{ number_format($rs->total_fees, 2) }}</td>
                        <td class="text-end pe-3 fw-bold">Rs. {{ number_format($rs->total_revenue, 2) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-3 text-muted">No rider performance records logged.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Delivery Orders Log Table -->
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-header bg-transparent border-0 pt-3 px-3 pb-0 d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0"><i class="bi bi-journal-text text-primary me-2"></i>Delivery Orders Log</h6>
        <span class="text-muted small">Showing recent delivery dispatch logs</span>
    </div>
    <div class="card-body p-0 mt-2">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Order #</th>
                        <th>Status</th>
                        <th>Rider</th>
                        <th>Delivery Area</th>
                        <th>Starting KM</th>
                        <th>Ending KM</th>
                        <th>Total KM</th>
                        <th>Delivery Fee</th>
                        <th class="text-end">Bill Amount</th>
                        <th class="text-center pe-3">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $o)
                    <tr>
                        <td class="ps-3 fw-bold">
                            <a href="{{ route('orders.show', $o->id) }}" class="text-decoration-none">
                                {{ $o->order_number }}
                            </a>
                            <div class="text-muted small fw-normal">{{ $o->created_at->format('d M, h:i A') }}</div>
                        </td>
                        <td>
                            @php
                                $statusClass = match($o->order_status) {
                                    'delivered', 'completed' => 'bg-success-subtle text-success border border-success-subtle',
                                    'out_for_delivery' => 'bg-primary-subtle text-primary border border-primary-subtle',
                                    'cancelled' => 'bg-danger-subtle text-danger border border-danger-subtle',
                                    default => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle'
                                };
                            @endphp
                            <span class="badge {{ $statusClass }} text-uppercase" style="font-size: 0.68rem;">
                                {{ str_replace('_', ' ', $o->order_status) }}
                            </span>
                        </td>
                        <td>
                            @if($o->rider)
                                <span class="fw-medium text-body"><i class="bi bi-person-fill text-muted me-1"></i>{{ $o->rider->name }}</span>
                                <div class="small text-muted" style="font-size: 0.72rem;">{{ $o->rider->vehicle_number ?: $o->rider->vehicle_type }}</div>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary">Unassigned</span>
                            @endif
                        </td>
                        <td>{{ $o->deliveryArea->name ?? ($o->customer_address ? Str::limit($o->customer_address, 25) : 'N/A') }}</td>
                        <td class="text-muted">{{ $o->rider_starting_km ? number_format($o->rider_starting_km, 1) : '—' }}</td>
                        <td class="text-muted">{{ $o->rider_ending_km ? number_format($o->rider_ending_km, 1) : '—' }}</td>
                        <td>
                            @php
                                $effKm = (float) ($o->rider_total_km > 0 ? $o->rider_total_km : ($o->delivery_distance_km > 0 ? $o->delivery_distance_km : ($o->deliveryArea?->estimated_distance_km ?: 0)));
                            @endphp
                            @if($effKm > 0)
                                <span class="badge bg-info-subtle text-info-emphasis fw-bold">
                                    {{ number_format($effKm, 1) }} KM
                                    @if(!($o->rider_total_km > 0))
                                        <small class="text-muted fw-normal" title="Estimated area distance">(Est.)</small>
                                    @endif
                                </span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="fw-medium text-success">Rs. {{ number_format($o->delivery_charge, 2) }}</td>
                        <td class="text-end fw-bold">Rs. {{ number_format($o->grand_total, 2) }}</td>
                        <td class="text-center pe-3">
                            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" 
                                    title="Log / Edit Rider Mileage"
                                    onclick="openMileageModal({{ $o->id }}, '{{ $o->order_number }}', '{{ $o->delivery_rider_id }}', '{{ $o->rider_starting_km }}', '{{ $o->rider_ending_km }}', '{{ $o->rider_total_km }}')">
                                <i class="bi bi-speedometer2 me-1"></i> Log KM
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center py-4 text-muted">No delivery orders found for the selected date range.</td>
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

<!-- MILEAGE UPDATE MODAL -->
<div class="modal fade" id="mileageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="mileageForm" method="POST" action="">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="mileageModalTitle">
                        <i class="bi bi-speedometer2 text-primary me-2"></i>Log Rider Mileage
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Assign Rider</label>
                        <select name="delivery_rider_id" id="modalRiderId" class="form-select">
                            <option value="">-- Select Rider --</option>
                            @foreach($riders as $r)
                                <option value="{{ $r->id }}">{{ $r->name }} ({{ $r->vehicle_type }}: {{ $r->vehicle_number ?: 'N/A' }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Starting KM (Odometer)</label>
                            <input type="number" step="0.1" name="rider_starting_km" id="modalStartingKm" class="form-control" placeholder="e.g. 1050.0" oninput="calculateModalKm()">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Ending KM (Odometer)</label>
                            <input type="number" step="0.1" name="rider_ending_km" id="modalEndingKm" class="form-control" placeholder="e.g. 1058.5" oninput="calculateModalKm()">
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Total Trip Distance (KM)</label>
                        <input type="number" step="0.1" name="rider_total_km" id="modalTotalKm" class="form-control fw-bold" placeholder="e.g. 8.5">
                        <small class="text-muted">Calculated automatically from starting & ending KM, or enter directly.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm fw-bold">Save Mileage</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function openMileageModal(orderId, orderNumber, riderId, startKm, endKm, totalKm) {
    const form = document.getElementById('mileageForm');
    form.action = `/restaurant/delivery/order/${orderId}/mileage`;
    document.getElementById('mileageModalTitle').innerHTML = `<i class="bi bi-speedometer2 text-primary me-2"></i>Log Mileage - Order #<strong>${orderNumber}</strong>`;
    
    document.getElementById('modalRiderId').value = riderId || '';
    document.getElementById('modalStartingKm').value = startKm || '';
    document.getElementById('modalEndingKm').value = endKm || '';
    document.getElementById('modalTotalKm').value = totalKm || '';

    const modal = bootstrap.Modal.getInstance(document.getElementById('mileageModal')) || new bootstrap.Modal(document.getElementById('mileageModal'));
    modal.show();
}

function calculateModalKm() {
    const start = parseFloat(document.getElementById('modalStartingKm').value);
    const end = parseFloat(document.getElementById('modalEndingKm').value);
    if (!isNaN(start) && !isNaN(end) && end >= start) {
        document.getElementById('modalTotalKm').value = (end - start).toFixed(1);
    }
}
</script>
@endpush
@endsection
