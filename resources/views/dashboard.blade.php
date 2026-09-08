@extends('layouts.app', ['title' => 'Dashboard - Food Point POS'])

@section('content')
<!-- Page Header with Quick Actions -->
<div class="page-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
  <div>
    <h1 class="page-title">Welcome, {{ auth()->user()->name ?? 'Administrator' }}</h1>
    <p class="text-muted mb-0">Here is the real-time operational overview of {{ \App\Models\SystemSetting::get('restaurant_name', 'Food Point POS') }}.</p>
  </div>
  <div class="d-flex align-items-center gap-2">
    <a href="{{ route('pos.index') }}" class="btn btn-primary btn-sm px-3 py-2 fw-semibold">
      <i class="ph-duotone ph-storefront me-1"></i> Open Live POS
    </a>
    <a href="{{ route('restaurant.kitchen') }}" class="btn btn-outline-secondary btn-sm px-3 py-2">
      <i class="ph-duotone ph-fork-knife me-1"></i> Kitchen KOT
    </a>
    <a href="{{ route('cash.shifts') }}" class="btn btn-outline-secondary btn-sm px-3 py-2">
      <i class="ph-duotone ph-vault me-1"></i> Cash Drawer
    </a>
  </div>
</div>

<!-- Stats Row 1: 4 Key Metrics -->
<div class="row g-4 mb-4">
  <!-- Today's Total Sales -->
  <div class="col-sm-6 col-xl-3">
    <div class="card widget-stat border h-100">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between mb-2">
          <div>
            <div class="widget-stat-label text-muted small text-uppercase fw-semibold">Today's Sales</div>
            <div class="widget-stat-value fs-4 fw-bold text-heading mt-1">Rs. {{ number_format($todaySales, 2) }}</div>
          </div>
          <div class="widget-stat-icon bg-primary-subtle text-primary p-2 rounded">
            <i class="bi bi-currency-dollar fs-4"></i>
          </div>
        </div>
        <div class="small text-muted">
          <span>{{ $todayOrdersCount }} orders completed today</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Dine-In Sales -->
  <div class="col-sm-6 col-xl-3">
    <div class="card widget-stat border h-100">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between mb-2">
          <div>
            <div class="widget-stat-label text-muted small text-uppercase fw-semibold">Dine-In Sales</div>
            <div class="widget-stat-value fs-4 fw-bold text-heading mt-1">Rs. {{ number_format($dineInSales, 2) }}</div>
          </div>
          <div class="widget-stat-icon bg-success-subtle text-success p-2 rounded">
            <i class="bi bi-cup-hot fs-4"></i>
          </div>
        </div>
        <div class="small text-muted">
          <span>{{ $openTablesCount }} of {{ $totalTablesCount }} tables occupied</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Delivery Sales -->
  <div class="col-sm-6 col-xl-3">
    <div class="card widget-stat border h-100">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between mb-2">
          <div>
            <div class="widget-stat-label text-muted small text-uppercase fw-semibold">Delivery Sales</div>
            <div class="widget-stat-value fs-4 fw-bold text-heading mt-1">Rs. {{ number_format($deliverySales, 2) }}</div>
          </div>
          <div class="widget-stat-icon bg-info-subtle text-info p-2 rounded">
            <i class="bi bi-bicycle fs-4"></i>
          </div>
        </div>
        <div class="small text-muted">
          <span>Doorstep courier dispatches</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Takeaway Sales -->
  <div class="col-sm-6 col-xl-3">
    <div class="card widget-stat border h-100">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between mb-2">
          <div>
            <div class="widget-stat-label text-muted small text-uppercase fw-semibold">Takeaway Sales</div>
            <div class="widget-stat-value fs-4 fw-bold text-heading mt-1">Rs. {{ number_format($takeawaySales, 2) }}</div>
          </div>
          <div class="widget-stat-icon bg-warning-subtle text-warning p-2 rounded">
            <i class="bi bi-bag-check fs-4"></i>
          </div>
        </div>
        <div class="small text-muted">
          <span>Counter quick pickups</span>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Row 2: Charts and Operational Status -->
<div class="row g-4 mb-4">
  <!-- Sales Breakdown Donut Chart -->
  <div class="col-lg-5">
    <div class="card border h-100">
      <div class="card-header bg-transparent py-3 d-flex align-items-center justify-content-between">
        <h5 class="card-title mb-0 fs-6 fw-bold">Revenue by Order Type</h5>
      </div>
      <div class="card-body">
        @if ($todaySales > 0)
          <div id="orderTypeChart" style="height: 260px;"></div>
        @else
          <div class="d-flex flex-column align-items-center justify-content-center h-100 py-5 text-muted">
            <i class="bi bi-pie-chart fs-1 mb-2 opacity-50"></i>
            <p class="mb-0">No sales finalized yet today.</p>
            <small>Completed orders will appear here dynamically.</small>
          </div>
        @endif
      </div>
    </div>
  </div>

  <!-- Active Operations Status & Alerts -->
  <div class="col-lg-7">
    <div class="card border h-100">
      <div class="card-header bg-transparent py-3 d-flex align-items-center justify-content-between">
        <h5 class="card-title mb-0 fs-6 fw-bold">Active Operations Overview</h5>
        <a href="{{ route('orders.index') }}" class="small text-primary text-decoration-none">View Orders &rarr;</a>
      </div>
      <div class="card-body">
        <div class="row g-3 mb-3">
          <div class="col-sm-4">
            <div class="p-3 rounded border text-center bg-surface">
              <div class="fs-4 fw-bold text-primary">{{ $openOrdersCount }}</div>
              <div class="small text-muted">Open / Unpaid Orders</div>
            </div>
          </div>
          <div class="col-sm-4">
            <div class="p-3 rounded border text-center bg-surface">
              <div class="fs-4 fw-bold text-warning">{{ $openTablesCount }} / {{ $totalTablesCount }}</div>
              <div class="small text-muted">Occupied Tables</div>
            </div>
          </div>
          <div class="col-sm-4">
            <div class="p-3 rounded border text-center bg-surface">
              <div class="fs-4 fw-bold {{ $lowStockCount > 0 ? 'text-danger' : 'text-success' }}">{{ $lowStockCount }}</div>
              <div class="small text-muted">Low Stock Alerts</div>
            </div>
          </div>
        </div>

        @if ($lowStockCount > 0)
          <div class="alert alert-warning py-2 px-3 small mb-0 d-flex align-items-center gap-2">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <div>
              <strong>Low Inventory:</strong> 
              {{ $lowStockProducts->pluck('name')->take(3)->implode(', ') }}
              @if ($lowStockCount > 3) and {{ $lowStockCount - 3 }} more @endif
            </div>
          </div>
        @endif
      </div>
    </div>
  </div>
</div>

<!-- Row 3: Recent Orders Table -->
<div class="card border">
  <div class="card-header bg-transparent py-3 d-flex align-items-center justify-content-between">
    <h5 class="card-title mb-0 fs-6 fw-bold">Recent POS Orders</h5>
    <a href="{{ route('orders.index') }}" class="btn btn-sm btn-outline-secondary">View All Invoices</a>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Order #</th>
            <th>Type</th>
            <th>Customer / Table</th>
            <th>Items</th>
            <th>Grand Total</th>
            <th>Paid</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($recentOrders as $order)
            <tr>
              <td class="fw-bold text-heading">
                <a href="{{ route('orders.show', $order->id) }}" class="text-decoration-none text-primary">
                  {{ $order->order_number }}
                </a>
              </td>
              <td>
                <span class="badge {{ $order->order_type === 'DINE_IN' ? 'bg-info' : ($order->order_type === 'DELIVERY' ? 'bg-primary' : 'bg-secondary') }}">
                  {{ $order->order_type }}
                </span>
              </td>
              <td>
                <div class="fw-semibold small">{{ $order->table_name ?: ($order->customer_name ?: 'Walk-in') }}</div>
                @if ($order->customer_phone)
                  <div class="text-muted" style="font-size: 0.75rem;">{{ $order->customer_phone }}</div>
                @endif
              </td>
              <td>
                <span class="small">{{ $order->items->count() }} items</span>
              </td>
              <td class="fw-bold">Rs. {{ number_format($order->grand_total, 2) }}</td>
              <td class="text-success small fw-semibold">Rs. {{ number_format($order->paid_amount, 2) }}</td>
              <td>
                @if ($order->isFinalized())
                  <span class="badge badge-soft-success">Completed</span>
                @else
                  <span class="badge badge-soft-warning">Open Draft</span>
                @endif
              </td>
              <td>
                <a href="{{ route('orders.show', $order->id) }}" class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size: 0.75rem;">
                  <i class="bi bi-eye"></i> View
                </a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="8" class="text-center py-4 text-muted">No orders created yet. Open the POS to place the first order!</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection

@push('scripts')
@if ($todaySales > 0)
<script>
  document.addEventListener('DOMContentLoaded', function() {
    const accentColor = getComputedStyle(document.documentElement).getPropertyValue('--accent-color').trim() || '#18181b';
    const successColor = getComputedStyle(document.documentElement).getPropertyValue('--success-color').trim() || '#0a863e';
    const infoColor = getComputedStyle(document.documentElement).getPropertyValue('--info-color').trim() || '#0891b2';

    const orderTypeChart = new ApexCharts(document.querySelector('#orderTypeChart'), {
      series: [{{ $typeDistribution['takeaway'] }}, {{ $typeDistribution['dine_in'] }}, {{ $typeDistribution['delivery'] }}],
      chart: {
        type: 'donut',
        height: 240
      },
      colors: ['#64748b', '#0a863e', '#0891b2'],
      labels: ['Takeaway', 'Dine-In', 'Delivery'],
      legend: {
        position: 'bottom',
        fontSize: '12px'
      },
      dataLabels: {
        enabled: false
      },
      tooltip: {
        y: {
          formatter: function(val) {
            return 'Rs. ' + Number(val).toLocaleString();
          }
        }
      }
    });
    orderTypeChart.render();
  });
</script>
@endif
@endpush
