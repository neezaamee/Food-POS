@extends('layouts.app', ['title' => 'Delivery Areas - Food Point POS'])

@section('content')
<div class="page-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
  <div>
    <h1 class="page-title">Delivery Areas & Pricing</h1>
    <nav class="breadcrumb small">
      <a href="{{ route('dashboard') }}" class="breadcrumb-item text-decoration-none">Home</a>
      <span class="breadcrumb-item active">Delivery Areas</span>
    </nav>
  </div>
  <div>
    <button type="button" class="btn btn-primary btn-sm px-3 py-2" data-bs-toggle="modal" data-bs-target="#addAreaModal">
      <i class="bi bi-plus-circle me-1"></i> Add Delivery Area
    </button>
  </div>
</div>

<div class="card border">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Area Name</th>
            <th>Code</th>
            <th>Default Delivery Charge</th>
            <th>Estimated Distance</th>
            <th>Total Orders Delivered</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($areas as $area)
            <tr>
              <td class="fw-bold text-heading">{{ $area->name }}</td>
              <td><span class="badge bg-secondary">{{ $area->code }}</span></td>
              <td class="fw-bold text-primary">Rs. {{ number_format($area->delivery_charge, 2) }}</td>
              <td>{{ $area->estimated_distance_km }} KM</td>
              <td>{{ $area->orders_count }} orders</td>
              <td>
                <span class="badge {{ $area->is_active ? 'badge-soft-success' : 'badge-soft-danger' }}">
                  {{ $area->is_active ? 'Active' : 'Inactive' }}
                </span>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="text-center py-4 text-muted">No delivery areas configured yet.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- ADD AREA MODAL -->
<div class="modal fade" id="addAreaModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="{{ route('restaurant.delivery.store') }}">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title fw-bold">Add Delivery Area</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Area Name</label>
            <input type="text" name="name" class="form-control" placeholder="e.g. Madina Town, Kohinoor City" required>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Area Code</label>
            <input type="text" name="code" class="form-control" placeholder="e.g. MT, KC" required>
          </div>
          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label small fw-semibold">Delivery Charge (Rs.)</label>
              <input type="number" name="delivery_charge" class="form-control" value="100.00" step="0.01" min="0" required>
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold">Est. Distance (KM)</label>
              <input type="number" name="estimated_distance_km" class="form-control" value="3.0" step="0.1" min="0" required>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Area</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
