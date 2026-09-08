@extends('layouts.app', ['title' => 'Stock Movement Ledger - Food Point POS'])

@section('content')
<div class="page-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
  <div>
    <h1 class="page-title">Stock Movement Ledger</h1>
    <nav class="breadcrumb small">
      <a href="{{ route('dashboard') }}" class="breadcrumb-item text-decoration-none">Home</a>
      <a href="{{ route('inventory.overview') }}" class="breadcrumb-item text-decoration-none">Inventory</a>
      <span class="breadcrumb-item active">Stock Ledger</span>
    </nav>
  </div>
</div>

<!-- Filter Card -->
<div class="card border mb-4">
  <div class="card-body p-3">
    <form method="GET" action="{{ route('inventory.ledger') }}" class="row g-2 align-items-center">
      <div class="col-md-5">
        <select name="product_id" class="form-select form-select-sm">
          <option value="">-- All Products --</option>
          @foreach ($products as $p)
            <option value="{{ $p->id }}" {{ request('product_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-4">
        <select name="movement_type" class="form-select form-select-sm">
          <option value="">-- All Movement Types --</option>
          <option value="opening" {{ request('movement_type') === 'opening' ? 'selected' : '' }}>Opening Stock</option>
          <option value="sale" {{ request('movement_type') === 'sale' ? 'selected' : '' }}>Sale Deduction (OUT)</option>
          <option value="sale_return" {{ request('movement_type') === 'sale_return' ? 'selected' : '' }}>Sale Return (IN)</option>
          <option value="adjustment" {{ request('movement_type') === 'adjustment' ? 'selected' : '' }}>Adjustment</option>
          <option value="purchase" {{ request('movement_type') === 'purchase' ? 'selected' : '' }}>Purchase (IN)</option>
        </select>
      </div>
      <div class="col-md-3 d-flex gap-2">
        <button type="submit" class="btn btn-sm btn-primary flex-grow-1">Filter</button>
        <a href="{{ route('inventory.ledger') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
      </div>
    </form>
  </div>
</div>

<div class="card border">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Date & Time</th>
            <th>Product</th>
            <th>Movement Type</th>
            <th class="text-center">Quantity</th>
            <th>Unit Cost</th>
            <th class="text-end">Balance After</th>
            <th>Notes</th>
            <th>Operator</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($movements as $m)
            <tr>
              <td class="small text-muted">{{ $m->created_at->format('d M Y, h:i A') }}</td>
              <td class="fw-semibold text-heading">{{ $m->product->name }}</td>
              <td>
                <span class="badge {{ $m->quantity > 0 ? 'bg-success' : 'bg-danger' }}">
                  {{ strtoupper(str_replace('_', ' ', $m->movement_type)) }}
                </span>
              </td>
              <td class="text-center fw-bold {{ $m->quantity > 0 ? 'text-success' : 'text-danger' }}">
                {{ $m->quantity > 0 ? '+' : '' }}{{ (int)$m->quantity }}
              </td>
              <td class="small">Rs. {{ number_format($m->unit_cost, 2) }}</td>
              <td class="text-end fw-bold">{{ (int)$m->balance_after }}</td>
              <td class="small text-muted">{{ $m->notes ?: '-' }}</td>
              <td class="small text-muted">{{ $m->user?->name ?? 'System' }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="8" class="text-center py-4 text-muted">No stock movements recorded yet.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
  @if ($movements->hasPages())
    <div class="card-footer bg-transparent py-2">
      {{ $movements->links() }}
    </div>
  @endif
</div>
@endsection
