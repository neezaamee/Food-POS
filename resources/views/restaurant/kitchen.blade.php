@extends('layouts.app', ['title' => 'Kitchen Display (KDS / KOT) - Food Point POS'])

@section('content')
<div class="page-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
  <div>
    <h1 class="page-title"><i class="ph-duotone ph-fork-knife me-2 text-primary"></i> Kitchen Order Tickets (KOT)</h1>
    <p class="text-muted mb-0">Live Kitchen Display System (KDS) for chef and preparation line.</p>
  </div>
  <div class="d-flex align-items-center gap-2">
    <button onclick="location.reload()" class="btn btn-outline-secondary btn-sm px-3 py-2">
      <i class="bi bi-arrow-clockwise me-1"></i> Refresh KOT
    </button>
  </div>
</div>

<div class="row g-4">
  @forelse ($activeOrders as $order)
    @php
      $kotStatus = $order->kot_status ?: 'prep';
      $kotBadgeClass = match($kotStatus) {
        'marination' => 'bg-warning text-dark',
        'baking' => 'bg-info text-white',
        'packing' => 'bg-primary text-white',
        'ready' => 'bg-success text-white',
        default => 'bg-secondary text-white'
      };
    @endphp
    <div class="col-md-6 col-xl-4">
      <div class="card border shadow-sm h-100">
        <!-- KOT Card Header -->
        <div class="card-header py-2 px-3 d-flex align-items-center justify-content-between {{ $order->order_type === 'DINE_IN' ? 'bg-info-subtle' : ($order->order_type === 'DELIVERY' ? 'bg-primary-subtle' : 'bg-warning-subtle') }}">
          <div>
            <span class="badge bg-dark me-1">{{ $order->order_type }}</span>
            <strong class="text-heading fs-6">{{ $order->order_number }}</strong>
            @if ($order->kots && $order->kots->count() > 1)
              <span class="badge bg-danger ms-1" title="Order has additional recalled items">
                {{ $order->kots->count() }} KOTs
              </span>
            @endif
          </div>
          <div class="d-flex align-items-center gap-1">
            <span class="badge {{ $kotBadgeClass }} text-uppercase px-2" style="font-size: 0.75rem;">
              {{ ucfirst($kotStatus) }}
            </span>
            <a href="{{ route('restaurant.kot.print', $order->id) }}" target="_blank" class="btn btn-sm btn-outline-dark py-0 px-2 ms-1" title="Print KOT Slip">
              <i class="bi bi-printer"></i> KOT
            </a>
          </div>
        </div>

        <!-- Meta -->
        <div class="px-3 py-2 bg-surface border-bottom d-flex justify-content-between small">
          <div>
            @if ($order->table_name)
              <span>Table: <strong>{{ $order->table_name }}</strong></span>
            @else
              <span>Cust: <strong>{{ $order->customer_name ?: 'Walk-in' }}</strong></span>
              @if ($order->customer_phone)
                <span class="text-muted">({{ $order->customer_phone }})</span>
              @endif
            @endif
          </div>
          <div class="text-muted">Placed: {{ $order->created_at->format('h:i A') }}</div>
        </div>

        <!-- Stage Selector Bar -->
        <div class="px-3 py-1 bg-light border-bottom d-flex align-items-center justify-content-between gap-1">
          <span class="small fw-bold text-muted" style="font-size: 0.72rem;">KOT STAGE:</span>
          <form method="POST" action="{{ route('restaurant.kitchen.order.status', $order->id) }}" class="d-flex gap-1 flex-wrap">
            @csrf
            <select name="kot_status" class="form-select form-select-sm py-0 ps-2 pe-4 fw-semibold" style="font-size: 0.75rem; height: 26px;" onchange="this.form.submit()">
              <option value="prep" {{ $kotStatus === 'prep' ? 'selected' : '' }}>1. Prep (تیار)</option>
              <option value="marination" {{ $kotStatus === 'marination' ? 'selected' : '' }}>2. Marination (میرینیشن)</option>
              <option value="baking" {{ $kotStatus === 'baking' ? 'selected' : '' }}>3. Baking / Cooking (بییکنگ)</option>
              <option value="packing" {{ $kotStatus === 'packing' ? 'selected' : '' }}>4. Packing (پیکنگ)</option>
              <option value="ready" {{ $kotStatus === 'ready' ? 'selected' : '' }}>5. Ready (تیار شدہ)</option>
            </select>
          </form>
        </div>

        <!-- Items Preparation Checklist -->
        <div class="card-body p-3">
          <div class="list-group list-group-flush">
            @foreach ($order->items as $item)
              <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center border-bottom">
                <div>
                  <div class="fw-bold text-heading">
                    <span class="badge bg-secondary me-1">{{ (int)$item->quantity }}x</span>
                    {{ $item->product_name }}
                    @php
                      $itemUrdu = $item->product_name_ur ?: $item->product?->name_ur;
                    @endphp
                    @if ($itemUrdu)
                      <span class="d-block text-primary small fw-semibold" style="font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', sans-serif; direction: rtl; text-align: left;">
                        {{ $itemUrdu }}
                      </span>
                    @endif
                  </div>
                  @php
                    $dealModel = $item->deal ?? \App\Models\Deal::where('product_id', $item->product_id)->with('items.product')->first();
                  @endphp
                  @if ($dealModel && $dealModel->items->isNotEmpty())
                    <div class="small text-muted ps-2 my-1">
                      @foreach ($dealModel->items as $dItem)
                        <div>
                          ↳ {{ (int)($dItem->quantity * $item->quantity) }}x {{ $dItem->product?->name }}
                          @if ($dItem->product?->name_ur)
                            <span style="font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', sans-serif;">({{ $dItem->product->name_ur }})</span>
                          @endif
                        </div>
                      @endforeach
                    </div>
                  @endif
                  @if ($item->notes)
                    <div class="small text-danger fw-semibold mt-1">
                      <i class="bi bi-exclamation-circle-fill me-1"></i> Note: {{ $item->notes }}
                    </div>
                  @endif
                </div>
                <div>
                  <form method="POST" action="{{ route('restaurant.kitchen.status', $item->id) }}" class="d-inline">
                    @csrf
                    @if ($item->status === 'ready')
                      <button type="submit" name="status" value="pending" class="btn btn-sm btn-success py-0 px-2 small">
                        <i class="bi bi-check-all"></i> Ready
                      </button>
                    @else
                      <button type="submit" name="status" value="ready" class="btn btn-sm btn-outline-secondary py-0 px-2 small">
                        <i class="bi bi-hourglass-split"></i> Prep
                      </button>
                    @endif
                  </form>
                </div>
              </div>
            @endforeach
          </div>

          @if ($order->notes)
            <div class="mt-3 p-2 bg-light-subtle rounded border small">
              <strong>Order Notes:</strong> {{ $order->notes }}
            </div>
          @endif
        </div>

        <div class="card-footer bg-transparent py-2 px-3 d-flex justify-content-between align-items-center small">
          <span class="text-muted">{{ $order->items->count() }} items</span>
          <a href="{{ route('pos.index', ['orderId' => $order->id]) }}" class="btn btn-sm btn-outline-primary py-0 px-2">
            Open in POS &rarr;
          </a>
        </div>
      </div>
    </div>
  @empty
    <div class="col-12 text-center py-5 text-muted">
      <i class="ph-duotone ph-check-circle fs-1 mb-2 text-success opacity-50"></i>
      <h5>All kitchen tickets are clear!</h5>
      <p class="mb-0">New dine-in, takeaway, and delivery orders will pop up here in real-time.</p>
    </div>
  @endforelse
</div>
@endsection
