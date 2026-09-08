@extends('layouts.app', ['title' => 'Invoice ' . $order->order_number . ' - Food Point POS'])

@section('content')
<div class="page-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 no-print">
  <div>
    <h1 class="page-title">Invoice #{{ $order->order_number }}</h1>
    <nav class="breadcrumb small">
      <a href="{{ route('dashboard') }}" class="breadcrumb-item text-decoration-none">Home</a>
      <a href="{{ route('orders.index') }}" class="breadcrumb-item text-decoration-none">Orders</a>
      <span class="breadcrumb-item active">{{ $order->order_number }}</span>
    </nav>
  </div>
  <div class="d-flex align-items-center gap-2">
    <button onclick="window.print()" class="btn btn-primary btn-sm px-3 py-2">
      <i class="bi bi-printer me-1"></i> Print A4 Invoice
    </button>
    <a href="{{ route('orders.thermal', $order->id) }}" target="_blank" class="btn btn-outline-secondary btn-sm px-3 py-2">
      <i class="bi bi-receipt me-1"></i> Thermal Bill
    </a>
    <a href="{{ route('restaurant.kot.print', $order->id) }}" target="_blank" class="btn btn-outline-dark btn-sm px-3 py-2">
      <i class="bi bi-printer me-1"></i> Kitchen KOT
    </a>
    @php
      $isShiftClosed = $order->cashShift && ! $order->cashShift->isOpen();
      $canCancel = auth()->user() && auth()->user()->canCancelOrder();
    @endphp

    @if ($order->order_status === 'cancelled')
      <span class="badge bg-danger fs-6 px-3 py-2"><i class="bi bi-x-circle me-1"></i> CANCELLED ORDER</span>
    @else
      @if ($order->isFinalized())
        <button type="button" class="btn btn-outline-danger btn-sm px-3 py-2" data-bs-toggle="modal" data-bs-target="#returnModal">
          <i class="bi bi-arrow-counter-clockwise me-1"></i> Refund / Return Order
        </button>
      @else
        <a href="{{ route('pos.index', ['orderId' => $order->id]) }}" class="btn btn-warning btn-sm px-3 py-2">
          <i class="bi bi-pencil me-1"></i> Edit in POS
        </a>
      @endif

      @if ($isShiftClosed)
        <span class="badge bg-secondary px-3 py-2" title="Shift #{{ $order->cash_shift_id }} is closed. Orders cannot be cancelled after shift close.">
          <i class="bi bi-lock me-1"></i> Shift Closed (Non-Cancellable)
        </span>
      @elseif ($canCancel)
        <button type="button" class="btn btn-danger btn-sm px-3 py-2" data-bs-toggle="modal" data-bs-target="#cancelModal">
          <i class="bi bi-x-circle me-1"></i> Cancel Order
        </button>
      @endif
    @endif
  </div>
</div>

<div class="row justify-content-center">
  <div class="col-lg-10">
    <!-- Invoice Card Styled directly from EasyAdmin invoice.html -->
    <div class="card invoice-card border shadow-sm mb-4">
      <div class="card-body p-4 p-lg-5">
        <!-- Invoice Header -->
        <div class="d-flex justify-content-between align-items-start border-bottom pb-4 mb-4">
          <div class="d-flex align-items-center gap-3">
            <img src="{{ \App\Models\SystemSetting::logoUrl() }}" alt="{{ \App\Models\SystemSetting::get('restaurant_name', 'Food Point') }}" style="max-height: 52px; max-width: 150px; object-fit: contain;">
            <div>
              <h3 class="fw-bold mb-0 text-heading">{{ \App\Models\SystemSetting::get('restaurant_name', 'Food Point Restaurant') }}</h3>
              <div class="text-muted small">{{ \App\Models\SystemSetting::get('tagline', 'Modern POS & Dining ERP') }}</div>
            </div>
          </div>
          <div class="text-end">
            <h2 class="fw-bold text-uppercase text-primary mb-1">INVOICE</h2>
            <div class="fw-bold fs-5 text-heading">{{ $order->order_number }}</div>
            <span class="badge {{ $order->isFinalized() ? 'bg-success' : 'bg-warning' }} mt-1">
              {{ $order->isFinalized() ? 'COMPLETED' : 'OPEN DRAFT' }}
            </span>
          </div>
        </div>

        <!-- Invoice Info (From / To / Meta) -->
        <div class="row mb-4">
          <div class="col-sm-6 mb-3">
            <h6 class="text-uppercase text-muted small fw-bold mb-2">Billed By (Merchant)</h6>
            <div class="fw-bold text-heading">{{ \App\Models\SystemSetting::get('restaurant_name', 'Food Point Express') }}</div>
            <div class="text-muted small">
              {{ \App\Models\SystemSetting::get('restaurant_address', 'Main Commercial Boulevard, D-Ground, Faisalabad') }}<br>
              @if (\App\Models\SystemSetting::get('restaurant_phone'))
                Tel: {{ \App\Models\SystemSetting::get('restaurant_phone') }}<br>
              @endif
              @if (\App\Models\SystemSetting::get('restaurant_email'))
                Email: {{ \App\Models\SystemSetting::get('restaurant_email') }}<br>
              @endif
              @if (\App\Models\SystemSetting::get('ntn_number') || \App\Models\SystemSetting::get('strn_number'))
                @if (\App\Models\SystemSetting::get('ntn_number')) NTN: {{ \App\Models\SystemSetting::get('ntn_number') }} @endif
                @if (\App\Models\SystemSetting::get('strn_number')) | STRN: {{ \App\Models\SystemSetting::get('strn_number') }} @endif
                <br>
              @endif
              Cashier: {{ $order->cashier?->name ?? 'Front Counter' }}
            </div>
          </div>
          <div class="col-sm-6 mb-3 text-sm-end">
            <h6 class="text-uppercase text-muted small fw-bold mb-2">Customer & Service</h6>
            <div class="fw-bold text-heading">{{ $order->customer_name ?: 'Walk-in Guest' }}</div>
            <div class="text-muted small">
              @if ($order->customer_phone) Phone: {{ $order->customer_phone }}<br> @endif
              @if ($order->customer_address) Address: {{ $order->customer_address }}<br> @endif
              Order Type: <strong>{{ $order->order_type }}</strong><br>
              @if ($order->table_name) Dine-In Table: <strong>{{ $order->table_name }}</strong><br> @endif
              @if ($order->order_type === 'DELIVERY')
                @if ($order->deliveryArea) Area: <strong>{{ $order->deliveryArea->name }}</strong><br> @endif
                @if ($order->rider) Rider: <strong>{{ $order->rider->name }}</strong> ({{ $order->rider->vehicle_number }})<br> @endif
              @endif
            </div>
          </div>
        </div>

        <div class="row bg-light-subtle py-2 px-3 rounded border mb-4 small">
          <div class="col-sm-4 mb-1 mb-sm-0">
            <span class="text-muted">Invoice Date:</span>
            <strong class="ms-1">{{ $order->created_at->format('M d, Y h:i A') }}</strong>
          </div>
          <div class="col-sm-4 mb-1 mb-sm-0 text-sm-center">
            <span class="text-muted">Payment Status:</span>
            <span class="badge {{ $order->payment_status === 'paid' ? 'bg-success' : ($order->payment_status === 'partially_paid' ? 'bg-warning' : 'bg-danger') }} ms-1">
              {{ strtoupper($order->payment_status) }}
            </span>
          </div>
          <div class="col-sm-4 text-sm-end">
            <span class="text-muted">FBR Invoice #:</span>
            <strong class="ms-1 text-primary">{{ $order->fbr_invoice_number ?: 'Pending / Local' }}</strong>
          </div>
        </div>

        <!-- Items Table -->
        <div class="table-responsive mb-4">
          <table class="table align-middle">
            <thead class="table-light">
              <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 50%;">Item & Cooking Notes</th>
                <th class="text-center" style="width: 15%;">Qty</th>
                <th class="text-end" style="width: 15%;">Unit Price</th>
                <th class="text-end" style="width: 15%;">Amount</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($order->items as $idx => $item)
                <tr>
                  <td>{{ $idx + 1 }}</td>
                  <td>
                    <div class="fw-semibold text-heading">{{ $item->product_name }}</div>
                    @php
                      $dealModel = $item->deal ?? \App\Models\Deal::where('product_id', $item->product_id)->with('items.product')->first();
                    @endphp
                    @if ($dealModel && $dealModel->items->isNotEmpty())
                      <div class="small text-muted ps-2 border-start border-2 border-primary my-1">
                        @foreach ($dealModel->items as $dItem)
                          <div>↳ {{ (int)($dItem->quantity * $item->quantity) }}x {{ $dItem->product?->name }}</div>
                        @endforeach
                      </div>
                    @endif
                    @if ($item->notes)
                      <div class="small text-warning"><i class="bi bi-chat-dots me-1"></i>{{ $item->notes }}</div>
                    @endif
                  </td>
                  <td class="text-center">{{ (int)$item->quantity }}</td>
                  <td class="text-end">Rs. {{ number_format($item->unit_price, 2) }}</td>
                  <td class="text-end fw-semibold">Rs. {{ number_format($item->subtotal, 2) }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        <!-- Summary Calculation Box -->
        <div class="row justify-content-end mb-4">
          <div class="col-sm-6 col-md-5">
            <table class="table table-sm table-borderless mb-0">
              <tbody>
                <tr>
                  <td class="text-muted">Subtotal:</td>
                  <td class="text-end fw-semibold">Rs. {{ number_format($order->subtotal, 2) }}</td>
                </tr>
                @if ($order->discount_amount > 0)
                  <tr>
                    <td class="text-muted">Discount:</td>
                    <td class="text-end text-danger">- Rs. {{ number_format($order->discount_amount, 2) }}</td>
                  </tr>
                @endif
                @if ($order->delivery_charge > 0)
                  <tr>
                    <td class="text-muted">Delivery Charges:</td>
                    <td class="text-end">+ Rs. {{ number_format($order->delivery_charge, 2) }}</td>
                  </tr>
                @endif
                @if ($order->tax_amount > 0)
                  <tr>
                    <td class="text-muted">Tax:</td>
                    <td class="text-end">+ Rs. {{ number_format($order->tax_amount, 2) }}</td>
                  </tr>
                @endif
                <tr class="border-top">
                  <td class="fw-bold fs-5 text-heading pt-2">Grand Total:</td>
                  <td class="text-end fw-bold fs-5 text-primary pt-2">Rs. {{ number_format($order->grand_total, 2) }}</td>
                </tr>
                <tr>
                  <td class="text-muted">Amount Paid:</td>
                  <td class="text-end text-success fw-bold">Rs. {{ number_format($order->paid_amount, 2) }}</td>
                </tr>
                @if ($order->balance_amount > 0)
                  <tr class="border-top">
                    <td class="fw-bold text-danger">Balance Due:</td>
                    <td class="text-end fw-bold text-danger">Rs. {{ number_format($order->balance_amount, 2) }}</td>
                  </tr>
                @endif
              </tbody>
            </table>
          </div>
        </div>

        <!-- Payments Log -->
        @if ($order->payments->isNotEmpty())
          <div class="border-top pt-3 mb-4">
            <h6 class="text-uppercase small text-muted fw-bold mb-2">Recorded Payments</h6>
            <div class="table-responsive">
              <table class="table table-sm table-bordered">
                <thead class="table-light">
                  <tr>
                    <th>Date</th>
                    <th>Payment Method</th>
                    <th>Reference / Auth Code</th>
                    <th class="text-end">Amount</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($order->payments as $p)
                    <tr>
                      <td class="small">{{ $p->created_at->format('d M Y, h:i A') }}</td>
                      <td><span class="badge bg-secondary">{{ ucfirst($p->payment_method) }}</span></td>
                      <td class="small text-muted">{{ $p->payment_reference ?: '-' }}</td>
                      <td class="text-end fw-semibold">Rs. {{ number_format($p->amount, 2) }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        @endif

        <!-- Footer Notes -->
        <div class="border-top pt-3 text-center text-muted small">
          <div>{{ \App\Models\SystemSetting::get('invoice_footer_note', 'Thank you for dining with Food Point POS! We appreciate your business.') }}</div>
          <div style="font-size: 0.75rem;">This is an electronically generated receipt verified by Double-Entry Ledger.</div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- RETURN / REFUND MODAL -->
@if ($order->isFinalized())
<div class="modal fade" id="returnModal" tabindex="-1" aria-labelledby="returnModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <form method="POST" action="{{ route('orders.return.process', $order->id) }}">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title fw-bold" id="returnModalLabel">
            <i class="bi bi-arrow-counter-clockwise me-2 text-danger"></i>
            Process Sale Return & Refund - Order #{{ $order->order_number }}
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <p class="small text-muted mb-0">Specify items and quantities to refund. Accounting ledger and cash drawer are automatically adjusted.</p>
            <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectAllForReturn()">
              <i class="bi bi-check2-all me-1"></i> Refund All Items
            </button>
          </div>
          
          <div class="table-responsive mb-3">
            <table class="table table-sm align-middle">
              <thead class="table-light">
                <tr>
                  <th>Product</th>
                  <th class="text-center">Sold Qty</th>
                  <th class="text-center" style="width: 150px;">Refund Qty</th>
                  <th class="text-end">Unit Price</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($order->items as $idx => $item)
                  <tr>
                    <td>
                      <div class="fw-semibold">{{ $item->product_name }}</div>
                      @if($item->product_name_ur)
                        <div class="small text-muted" dir="rtl">{{ $item->product_name_ur }}</div>
                      @endif
                    </td>
                    <td class="text-center">{{ (int)$item->quantity }}</td>
                    <td>
                      <input type="hidden" name="items[{{ $idx }}][order_item_id]" value="{{ $item->id }}">
                      <input type="number" name="items[{{ $idx }}][quantity]" value="0" min="0" max="{{ (int)$item->quantity }}" class="form-control form-control-sm text-center return-qty-input">
                    </td>
                    <td class="text-end">Rs. {{ number_format($item->unit_price, 2) }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Refund Method</label>
              <select name="refund_method" class="form-select">
                <option value="cash">Cash Refund (Deducted from Active Cash Drawer)</option>
                <option value="credit">Customer Account Credit Reduction</option>
                <option value="bank">Bank / Card Payment Reversal</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Return / Refund Reason</label>
              <input type="text" name="reason" class="form-control" placeholder="e.g. Customer rejected delivery, wrong item, food issue" required>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger fw-bold">
            <i class="bi bi-arrow-counter-clockwise me-1"></i> Confirm Sale Return & Refund
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endif

<!-- CANCEL ORDER MODAL (Both Draft and Completed Orders) -->
@if ($order->order_status !== 'cancelled' && (!$order->cashShift || $order->cashShift->isOpen()) && auth()->user()?->canCancelOrder())
<div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="{{ route('orders.cancel', $order->id) }}">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title fw-bold text-danger" id="cancelModalLabel">
            <i class="bi bi-x-circle me-2"></i> Cancel Order #{{ $order->order_number }}
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          @if ($order->isFinalized())
            <div class="alert alert-warning small mb-3">
              <i class="bi bi-exclamation-triangle-fill me-1"></i>
              <strong>Notice:</strong> This order is completed. Cancelling will:
              <ul class="mb-0 mt-1 ps-3">
                <li>Reverse sales revenue & taxes in Double-Entry Accounting.</li>
                @php
                  $cashCollected = (float)$order->payments()->where('payment_method', 'cash')->sum('amount');
                @endphp
                @if ($cashCollected > 0)
                  <li>Deduct <strong>Rs. {{ number_format($cashCollected, 2) }}</strong> cash from active Shift drawer sales.</li>
                @endif
                @if ($order->table)
                  <li>Release dining table <strong>{{ $order->table_name }}</strong>.</li>
                @endif
              </ul>
            </div>

            <div class="mb-3 p-3 bg-light rounded border">
              <label class="form-label small fw-bold text-heading mb-2">Inventory Action</label>
              <div class="form-check mb-2">
                <input class="form-check-input" type="radio" name="is_waste" id="restockRadio" value="0" checked>
                <label class="form-check-label small" for="restockRadio">
                  <strong>Restock items to inventory</strong> (Items are fresh / can be reused)
                </label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="is_waste" id="wasteRadio" value="1">
                <label class="form-check-label small text-danger" for="wasteRadio">
                  <strong>Mark as Waste / Spoilage</strong> (Food was already cooked and cannot be reused)
                </label>
              </div>
            </div>
          @else
            <p class="text-muted small">Are you sure you want to cancel this open draft order? This will release any occupied dining table and mark the order as Cancelled.</p>
          @endif

          <div class="mb-3">
            <label class="form-label small fw-semibold">Cancellation Reason <span class="text-danger">*</span></label>
            <input type="text" name="reason" class="form-control" placeholder="e.g. Customer rejected delivery, cancelled before prep, wrong order" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-danger btn-sm fw-bold">
            <i class="bi bi-x-circle me-1"></i> Confirm Cancellation
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endif

@push('scripts')
<script>
function selectAllForReturn() {
  document.querySelectorAll('.return-qty-input').forEach(input => {
    input.value = input.getAttribute('max') || 0;
  });
}
</script>
@endpush
@endsection
