@extends('layouts.app', ['title' => 'Sale Orders & Invoices - Food Point POS'])

@section('content')
<div class="page-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
  <div>
    <h1 class="page-title">Sale Orders & Invoices</h1>
    <nav class="breadcrumb small">
      <a href="{{ route('dashboard') }}" class="breadcrumb-item text-decoration-none">Home</a>
      <span class="breadcrumb-item active">Orders</span>
    </nav>
  </div>
  <div>
    <a href="{{ route('pos.index') }}" class="btn btn-primary btn-sm px-3 py-2 fw-semibold">
      <i class="ph-duotone ph-storefront me-1"></i> New POS Order
    </a>
  </div>
</div>

<!-- Search & Filter Card -->
<div class="card border mb-4">
  <div class="card-body p-3">
    <form method="GET" action="{{ route('orders.index') }}" class="row g-2 align-items-center">
      <div class="col-md-4">
        <div class="input-group input-group-sm">
          <span class="input-group-text bg-surface"><i class="bi bi-search"></i></span>
          <input type="text" name="search" class="form-control" placeholder="Search order #, customer, phone..." value="{{ request('search') }}">
        </div>
      </div>
      <div class="col-md-3">
        <select name="type" class="form-select form-select-sm">
          <option value="">-- All Order Types --</option>
          <option value="TAKEAWAY" {{ request('type') === 'TAKEAWAY' ? 'selected' : '' }}>Takeaway</option>
          <option value="DINE_IN" {{ request('type') === 'DINE_IN' ? 'selected' : '' }}>Dine-In</option>
          <option value="DELIVERY" {{ request('type') === 'DELIVERY' ? 'selected' : '' }}>Delivery</option>
        </select>
      </div>
      <div class="col-md-3">
        <select name="status" class="form-select form-select-sm">
          <option value="">-- All Statuses --</option>
          <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed / Finalized</option>
          <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Open Draft</option>
        </select>
      </div>
      <div class="col-md-2 d-flex gap-2">
        <button type="submit" class="btn btn-sm btn-primary flex-grow-1">Filter</button>
        <a href="{{ route('orders.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
      </div>
    </form>
  </div>
</div>

<!-- Orders Table Card -->
<div class="card border">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0 text-nowrap">
        <thead class="table-light">
          <tr>
            <th>Order #</th>
            <th>Date & Time</th>
            <th>Type</th>
            <th>Table / Customer</th>
            <th>Subtotal</th>
            <th>Grand Total</th>
            <th>Payment</th>
            <th>Status</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($orders as $order)
            <tr>
              <td class="fw-bold">
                <a href="{{ route('orders.show', $order->id) }}" class="text-primary text-decoration-none">
                  {{ $order->order_number }}
                </a>
              </td>
              <td class="small text-muted">
                <div class="fw-medium text-dark">{{ $order->created_at->format('d M Y') }}</div>
                <div style="font-size: 0.75rem;">{{ $order->created_at->format('h:i A') }}</div>
              </td>
              <td>
                @if ($order->order_type === 'DINE_IN')
                  <span class="badge badge-soft-info"><i class="ph-duotone ph-fork-knife me-1"></i> Dine-In</span>
                @elseif ($order->order_type === 'DELIVERY')
                  <span class="badge badge-soft-primary"><i class="ph-duotone ph-moped me-1"></i> Delivery</span>
                @else
                  <span class="badge badge-soft-warning"><i class="ph-duotone ph-bag me-1"></i> Takeaway</span>
                @endif
              </td>
              <td>
                @if ($order->order_type === 'DINE_IN')
                  <div class="fw-semibold small text-primary"><i class="ph-duotone ph-chair me-1"></i>{{ $order->table_name ?: 'Table Not Set' }}</div>
                  <div class="text-muted" style="font-size: 0.75rem;">{{ $order->customer_name ?: 'Walk-in' }}</div>
                @else
                  <div class="fw-semibold small">{{ $order->customer_name ?: 'Walk-in' }}</div>
                  @if ($order->customer_phone)
                    <div class="text-muted" style="font-size: 0.75rem;"><i class="bi bi-telephone me-1"></i>{{ $order->customer_phone }}</div>
                  @endif
                @endif
              </td>
              <td class="small text-muted">Rs. {{ number_format($order->subtotal, 2) }}</td>
              <td class="fw-bold text-heading">Rs. {{ number_format($order->grand_total, 2) }}</td>
              <td>
                @if ($order->paid_amount >= $order->grand_total && $order->grand_total > 0)
                  <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.75rem;">
                    <i class="bi bi-check-circle-fill me-1"></i>Paid
                  </span>
                @elseif ($order->paid_amount > 0)
                  <span class="badge bg-warning-subtle text-warning border border-warning-subtle" style="font-size: 0.75rem;">
                    Partial (Rs. {{ number_format($order->paid_amount, 0) }})
                  </span>
                @else
                  <span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size: 0.75rem;">
                    Unpaid
                  </span>
                @endif
              </td>
              <td>
                @if ($order->order_status === 'cancelled')
                  <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                    <i class="bi bi-x-circle me-1"></i>Cancelled
                  </span>
                @elseif ($order->isFinalized())
                  <span class="badge badge-soft-success">
                    <i class="bi bi-check-lg me-1"></i>Completed
                  </span>
                @else
                  <span class="badge badge-soft-warning">
                    <i class="bi bi-clock me-1"></i>Open Draft
                  </span>
                  @if ($order->kot_status)
                    <div class="mt-1">
                      <span class="badge bg-secondary-subtle text-secondary border text-uppercase" style="font-size: 0.65rem;">
                        KOT: {{ $order->kot_status }}
                      </span>
                    </div>
                  @endif
                @endif
              </td>
              <td class="text-end">
                <div class="btn-group btn-group-sm">
                  <a href="{{ route('orders.show', $order->id) }}" class="btn btn-outline-secondary" title="View Details / Invoice">
                    <i class="bi bi-eye"></i>
                  </a>
                  <a href="{{ route('orders.thermal', $order->id) }}" target="_blank" class="btn btn-outline-secondary" title="Thermal Receipt">
                    <i class="bi bi-printer"></i>
                  </a>
                  @if ($order->isFinalized())
                    <a href="{{ route('orders.show', $order->id) }}#returnModal" class="btn btn-outline-danger" title="Refund / Return Order">
                      <i class="bi bi-arrow-counter-clockwise"></i>
                    </a>
                  @elseif ($order->order_status !== 'cancelled')
                    <a href="{{ route('pos.index', ['orderId' => $order->id]) }}" class="btn btn-primary" title="Resume in POS">
                      <i class="bi bi-play-fill"></i>
                    </a>
                    <form method="POST" action="{{ route('orders.cancel', $order->id) }}" class="d-inline" onsubmit="return confirm('Cancel Order #{{ $order->order_number }}?');">
                      @csrf
                      <button type="submit" class="btn btn-outline-danger" title="Cancel Order">
                        <i class="bi bi-x-circle"></i>
                      </button>
                    </form>
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="9" class="text-center py-4 text-muted">No orders found matching the filter criteria.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
  @if ($orders->hasPages())
    <div class="card-footer bg-transparent py-3 px-3 border-top">
      {{ $orders->withQueryString()->links('pagination::bootstrap-5') }}
    </div>
  @endif
</div>
@endsection
