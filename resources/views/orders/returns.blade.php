@extends('layouts.app', ['title' => 'Sale Returns & Credit Notes - Food Point POS'])

@section('content')
<div class="page-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
  <div>
    <h1 class="page-title">Sale Returns & Credit Notes</h1>
    <nav class="breadcrumb small">
      <a href="{{ route('dashboard') }}" class="breadcrumb-item text-decoration-none">Home</a>
      <a href="{{ route('orders.index') }}" class="breadcrumb-item text-decoration-none">Orders</a>
      <span class="breadcrumb-item active">Sale Returns</span>
    </nav>
  </div>
</div>

<div class="card border">
  <div class="card-header bg-transparent py-3">
    <h5 class="card-title mb-0 fs-6 fw-bold">Returned Invoices & Restocked Items</h5>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Return #</th>
            <th>Date</th>
            <th>Original Order #</th>
            <th>Customer</th>
            <th>Items Returned</th>
            <th>Refund Method</th>
            <th>Reason</th>
            <th>Refund Amount</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($returns as $ret)
            <tr>
              <td class="fw-bold text-danger">{{ $ret->return_number }}</td>
              <td class="small text-muted">{{ $ret->created_at->format('d M Y, h:i A') }}</td>
              <td>
                <a href="{{ route('orders.show', $ret->order_id) }}" class="text-primary fw-semibold text-decoration-none">
                  {{ $ret->order->order_number }}
                </a>
              </td>
              <td>{{ $ret->customer?->name ?? 'Walk-in' }}</td>
              <td>
                <ul class="list-unstyled mb-0 small">
                  @foreach ($ret->items as $rItem)
                    <li>{{ (int)$rItem->quantity }}x {{ $rItem->product->name }}</li>
                  @endforeach
                </ul>
              </td>
              <td>
                <span class="badge bg-secondary">{{ ucfirst($ret->refund_method) }}</span>
              </td>
              <td class="small text-muted">{{ $ret->reason }}</td>
              <td class="fw-bold text-danger">Rs. {{ number_format($ret->grand_total, 2) }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="8" class="text-center py-4 text-muted">No sale returns recorded yet.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
  @if ($returns->hasPages())
    <div class="card-footer bg-transparent py-2">
      {{ $returns->links() }}
    </div>
  @endif
</div>
@endsection
