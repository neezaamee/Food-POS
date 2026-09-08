@extends('layouts.app', ['title' => 'Units - Food Point POS'])

@section('content')
<div class="page-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
  <div>
    <h1 class="page-title">Measurement Units</h1>
    <nav class="breadcrumb small">
      <a href="{{ route('dashboard') }}" class="breadcrumb-item text-decoration-none">Home</a>
      <span class="breadcrumb-item active">Units</span>
    </nav>
  </div>
  <div>
    <button type="button" class="btn btn-primary btn-sm px-3 py-2" data-bs-toggle="modal" data-bs-target="#addUnitModal">
      <i class="bi bi-plus-circle me-1"></i> Add Unit
    </button>
  </div>
</div>

<div class="card border">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Unit Name</th>
            <th>Unit Code</th>
            <th>Products Associated</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($units as $u)
            <tr>
              <td class="fw-bold text-heading">{{ $u->name }}</td>
              <td><span class="badge bg-secondary">{{ $u->code }}</span></td>
              <td>{{ $u->products_count }} items</td>
            </tr>
          @empty
            <tr>
              <td colspan="3" class="text-center py-4 text-muted">No units registered yet.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="modal fade" id="addUnitModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="{{ route('resources.units.store') }}">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title fw-bold">Add Measurement Unit</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Unit Name</label>
            <input type="text" name="name" class="form-control" placeholder="e.g. Piece, Plate, Box" required>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Unit Code</label>
            <input type="text" name="code" class="form-control" placeholder="e.g. PCS, PLT, BOX" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Unit</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
