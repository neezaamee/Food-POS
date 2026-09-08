@extends('layouts.app', ['title' => 'Stock Adjustments - Food Point POS'])

@section('content')
<div class="page-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
  <div>
    <h1 class="page-title">Stock Adjustments</h1>
    <nav class="breadcrumb small">
      <a href="{{ route('dashboard') }}" class="breadcrumb-item text-decoration-none">Home</a>
      <a href="{{ route('inventory.overview') }}" class="breadcrumb-item text-decoration-none">Inventory</a>
      <span class="breadcrumb-item active">Adjustments</span>
    </nav>
  </div>
  <div>
    <button type="button" class="btn btn-primary btn-sm px-3 py-2" data-bs-toggle="modal" data-bs-target="#addAdjustmentModal">
      <i class="bi bi-plus-circle me-1"></i> New Adjustment
    </button>
  </div>
</div>

<div class="card border">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Adjustment #</th>
            <th>Date</th>
            <th>Product</th>
            <th>Type</th>
            <th>Quantity</th>
            <th>Reason</th>
            <th>Authorized User</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($adjustments as $adj)
            <tr>
              <td class="fw-bold">{{ $adj->adjustment_number }}</td>
              <td class="small text-muted">{{ $adj->created_at->format('d M Y, h:i A') }}</td>
              <td class="fw-semibold text-heading">{{ $adj->product->name }}</td>
              <td>
                <span class="badge {{ $adj->type === 'addition' ? 'bg-success' : 'bg-danger' }}">
                  {{ ucfirst($adj->type) }}
                </span>
              </td>
              <td class="fw-bold {{ $adj->type === 'addition' ? 'text-success' : 'text-danger' }}">
                {{ $adj->type === 'addition' ? '+' : '-' }}{{ (int)$adj->quantity }}
              </td>
              <td class="small text-muted">{{ $adj->reason }}</td>
              <td class="small text-muted">{{ $adj->user?->name ?? 'Admin' }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-center py-4 text-muted">No stock adjustments recorded.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
  @if ($adjustments->hasPages())
    <div class="card-footer bg-transparent py-2">
      {{ $adjustments->links() }}
    </div>
  @endif
</div>

<div class="modal fade" id="addAdjustmentModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="{{ route('inventory.adjustments.store') }}">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title fw-bold">Record Physical Stock Adjustment</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Product</label>
            <select name="product_id" class="form-select" required>
              <option value="">-- Choose Product --</option>
              @foreach ($products as $p)
                <option value="{{ $p->id }}">{{ $p->name }} (Current: {{ (int)$p->current_stock }})</option>
              @endforeach
            </select>
          </div>
          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label small fw-semibold">Adjustment Type</label>
              <select name="type" class="form-select" required>
                <option value="addition">Addition (+ IN)</option>
                <option value="subtraction">Subtraction (- OUT)</option>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold">Quantity</label>
              <input type="number" name="quantity" class="form-control" value="1" min="1" required>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Reason for Adjustment</label>
            <input type="text" name="reason" class="form-control" placeholder="e.g. Physical audit recount, wastage, breakage" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Post Adjustment</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
