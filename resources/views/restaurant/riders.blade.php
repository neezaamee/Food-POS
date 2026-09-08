@extends('layouts.app', ['title' => 'Delivery Riders - Food Point POS'])

@section('content')
<div class="page-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
  <div>
    <h1 class="page-title">Delivery Riders & Mileage Fleet</h1>
    <nav class="breadcrumb small">
      <a href="{{ route('dashboard') }}" class="breadcrumb-item text-decoration-none">Home</a>
      <span class="breadcrumb-item active">Delivery Riders</span>
    </nav>
  </div>
  <div>
    <button type="button" class="btn btn-primary btn-sm px-3 py-2" data-bs-toggle="modal" data-bs-target="#addRiderModal">
      <i class="bi bi-person-plus me-1"></i> Add Rider
    </button>
  </div>
</div>

<div class="card border">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Rider Name</th>
            <th>Emp ID</th>
            <th>Mobile</th>
            <th>Vehicle</th>
            <th>Vehicle #</th>
            <th>Total Deliveries</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($riders as $rider)
            <tr>
              <td class="fw-bold text-heading">{{ $rider->name }}</td>
              <td><span class="badge bg-secondary">{{ $rider->employee_id }}</span></td>
              <td>{{ $rider->mobile }}</td>
              <td>{{ $rider->vehicle_type }}</td>
              <td class="small text-muted">{{ $rider->vehicle_number ?: '-' }}</td>
              <td>{{ $rider->orders_count }} orders</td>
              <td>
                <span class="badge {{ $rider->status === 'available' ? 'bg-success' : ($rider->status === 'on_delivery' ? 'bg-warning' : 'bg-secondary') }}">
                  {{ ucfirst(str_replace('_', ' ', $rider->status)) }}
                </span>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-center py-4 text-muted">No riders registered in fleet yet.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- ADD RIDER MODAL -->
<div class="modal fade" id="addRiderModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="{{ route('restaurant.riders.store') }}">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title fw-bold">Register Delivery Rider</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Rider Full Name</label>
            <input type="text" name="name" class="form-control" placeholder="e.g. Ali Raza" required>
          </div>
          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label small fw-semibold">Mobile Number</label>
              <input type="text" name="mobile" class="form-control" placeholder="0300-1234567" required>
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold">Employee ID</label>
              <input type="text" name="employee_id" class="form-control" placeholder="RDR-104" required>
            </div>
          </div>
          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label small fw-semibold">Vehicle Type</label>
              <input type="text" name="vehicle_type" class="form-control" value="Motorbike" required>
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold">Vehicle Number</label>
              <input type="text" name="vehicle_number" class="form-control" placeholder="e.g. FSD-1234">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Rider</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
