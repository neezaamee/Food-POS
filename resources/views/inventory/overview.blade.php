@extends('layouts.app', ['title' => 'Stock Overview - Food Point POS'])

@section('content')
<div class="page-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
  <div>
    <h1 class="page-title">Inventory Stock Overview</h1>
    <nav class="breadcrumb small">
      <a href="{{ route('dashboard') }}" class="breadcrumb-item text-decoration-none">Home</a>
      <span class="breadcrumb-item active">Inventory</span>
    </nav>
  </div>
  <div class="d-flex align-items-center gap-2">
    <a href="{{ route('inventory.adjustments') }}" class="btn btn-outline-secondary btn-sm px-3 py-2">
      <i class="bi bi-sliders me-1"></i> Stock Adjustments
    </a>
    <a href="{{ route('inventory.purchases') }}" class="btn btn-primary btn-sm px-3 py-2">
      <i class="bi bi-plus-circle me-1"></i> New Purchase
    </a>
  </div>
</div>

<!-- Stats Row -->
<div class="row g-4 mb-4">
  <div class="col-sm-6 col-xl-4">
    <div class="card border p-3">
      <div class="small text-muted text-uppercase fw-semibold">Total Stock Valuation</div>
      <div class="fs-4 fw-bold text-primary mt-1">Rs. {{ number_format($totalStockValue, 2) }}</div>
      <div class="small text-muted mt-1">Valued at product cost price</div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-4">
    <div class="card border p-3">
      <div class="small text-muted text-uppercase fw-semibold">Total Inventory Items</div>
      <div class="fs-4 fw-bold text-heading mt-1">{{ $products->count() }} items</div>
      <div class="small text-muted mt-1">Across all menu categories</div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-4">
    <div class="card border p-3">
      <div class="small text-muted text-uppercase fw-semibold">Low Stock Alerts</div>
      <div class="fs-4 fw-bold {{ $lowStockCount > 0 ? 'text-danger' : 'text-success' }} mt-1">{{ $lowStockCount }} items</div>
      <div class="small text-muted mt-1">Requires procurement restock</div>
    </div>
  </div>
</div>

<div class="card border">
  <div class="card-header bg-transparent py-3">
    <h5 class="card-title mb-0 fs-6 fw-bold">Current Stock Level by Product</h5>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Product Name</th>
            <th>Type</th>
            <th>Category</th>
            <th>Unit Cost</th>
            <th>Current Stock</th>
            <th>Alert Level</th>
            <th>Valuation</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($products as $p)
            <tr>
              <td>
                <div class="fw-bold text-heading">{{ $p->name }}</div>
                @if($p->isMenuItem() && $p->hasRecipe())
                  <div class="small text-success"><i class="bi bi-check-circle me-1"></i>BOM Recipe Active ({{ $p->recipeItems->count() }} ingredients)</div>
                @elseif($p->isMenuItem())
                  <div class="small text-muted"><i class="bi bi-exclamation-circle me-1"></i>No recipe linked</div>
                @endif
              </td>
              <td>
                @if($p->type === 'raw_material')
                  <span class="badge bg-warning text-dark"><i class="bi bi-egg me-1"></i>Raw Material</span>
                @elseif($p->type === 'menu_item')
                  <span class="badge bg-info text-dark"><i class="bi bi-cup-hot me-1"></i>Menu Dish</span>
                @else
                  <span class="badge bg-secondary"><i class="bi bi-box-seam me-1"></i>Retail Goods</span>
                @endif
              </td>
              <td>{{ $p->category?->name ?? '-' }}</td>
              <td class="small">Rs. {{ number_format($p->cost_price, 2) }}</td>
              <td class="fw-bold fs-6">
                {{ rtrim(rtrim(number_format($p->current_stock, 3), '0'), '.') }} {{ $p->unit?->code }}
              </td>
              <td class="small text-muted">{{ rtrim(rtrim(number_format($p->min_stock, 3), '0'), '.') }} {{ $p->unit?->code }}</td>
              <td class="fw-semibold">Rs. {{ number_format($p->current_stock * $p->cost_price, 2) }}</td>
              <td>
                <span class="badge {{ $p->isLowStock() ? 'bg-danger' : 'bg-success' }}">
                  {{ $p->isLowStock() ? 'LOW STOCK' : 'IN STOCK' }}
                </span>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
