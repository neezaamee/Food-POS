<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Receipt - {{ $order->order_number }}</title>
  <style>
    @page {
      margin: 0;
      size: 80mm auto;
    }
    * {
      -webkit-print-color-adjust: exact !important;
      print-color-adjust: exact !important;
      color-adjust: exact !important;
      box-sizing: border-box;
    }
    body {
      font-family: 'Courier New', Courier, monospace;
      font-size: 13px;
      line-height: 1.3;
      margin: 0;
      padding: 24px 10px;
      color: #000;
      background: #f1f5f9;
      display: flex;
      flex-direction: column;
      align-items: center;
      min-height: 100vh;
    }
    .receipt-paper {
      width: 80mm;
      background: #fff;
      padding: 12px 14px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.12);
      border: 1px solid #e2e8f0;
      border-radius: 4px;
    }
    img.receipt-logo {
      display: block;
      margin: 0 auto 6px auto;
      max-height: 52px;
      max-width: 150px;
      object-fit: contain;
      image-rendering: -webkit-optimize-contrast;
      image-rendering: crisp-edges;
    }
    .text-center { text-align: center; }
    .text-end { text-align: right; }
    .text-start { text-align: left; }
    .fw-bold { font-weight: bold; }
    .border-top { border-top: 1px dashed #000; }
    .border-bottom { border-bottom: 1px dashed #000; }
    .my-1 { margin-top: 4px; margin-bottom: 4px; }
    .my-2 { margin-top: 8px; margin-bottom: 8px; }
    .py-1 { padding-top: 4px; padding-bottom: 4px; }
    table { width: 100%; border-collapse: collapse; table-layout: fixed; }
    th, td { font-size: 12px; }
    .no-print {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      margin-bottom: 16px;
      width: 80mm;
    }
    .btn-action {
      font-family: system-ui, -apple-system, sans-serif;
      font-size: 13px;
      font-weight: 600;
      padding: 7px 18px;
      border-radius: 6px;
      cursor: pointer;
      border: none;
      transition: opacity 0.15s;
    }
    .btn-action:hover { opacity: 0.9; }
    .btn-primary { background: #0d6efd; color: #fff; }
    .btn-secondary { background: #64748b; color: #fff; }

    @media print {
      body {
        background: #fff !important;
        padding: 0 !important;
        margin: 0 !important;
        width: 80mm !important;
        display: block !important;
      }
      .receipt-paper {
        width: 80mm !important;
        box-shadow: none !important;
        border: none !important;
        border-radius: 0 !important;
        padding: 4mm 4mm !important;
        margin: 0 !important;
      }
      .no-print {
        display: none !important;
      }
    }
  </style>
</head>
<body onload="window.print()">
  <div class="no-print">
    <button onclick="window.print()" class="btn-action btn-primary">🖨️ Print Receipt</button>
    <button onclick="window.close()" class="btn-action btn-secondary">✕ Close (Esc)</button>
  </div>

  <div class="receipt-paper">

  @php
    $currency = \App\Models\SystemSetting::get('currency', 'Rs.');
    $restName = \App\Models\SystemSetting::get('restaurant_name', 'FOOD POINT');
    $tagline = \App\Models\SystemSetting::get('tagline', '');
    $address = \App\Models\SystemSetting::get('restaurant_address', '');
    $phone = \App\Models\SystemSetting::get('restaurant_phone', '');
    $ntn = \App\Models\SystemSetting::get('ntn_number', '');
    $strn = \App\Models\SystemSetting::get('strn_number', '');
    $footerNote = \App\Models\SystemSetting::get('invoice_footer_note', 'Thank you for dining with us! Please visit again.');
  @endphp

  <div class="text-center">
    <img src="{{ \App\Models\SystemSetting::logoUrl() }}" class="receipt-logo" alt="{{ $restName }}"><br>
    <h2 style="margin: 0; font-size: 17px; text-transform: uppercase;">{{ $restName }}</h2>
    @if ($tagline)
      <div style="font-size: 11px;">{{ $tagline }}</div>
    @endif
    @if ($address)
      <div style="font-size: 11px;">{{ $address }}</div>
    @endif
    @if ($phone)
      <div style="font-size: 11px;">Tel: {{ $phone }}</div>
    @endif
    @if ($ntn || $strn)
      <div style="font-size: 10px;">
        @if ($ntn) NTN: {{ $ntn }} @endif
        @if ($strn) | STRN: {{ $strn }} @endif
      </div>
    @endif
  </div>

  <div class="border-top border-bottom py-1 my-2 text-center fw-bold">
    @if (! $order->isFinalized() || $order->payment_status !== 'paid')
      *** {{ $order->order_type }} - UNPAID BILL ***
      <div style="font-size: 11px; margin-top: 2px;">(PAYMENT PENDING)</div>
    @else
      *** {{ $order->order_type }} RECEIPT ***
    @endif
  </div>

  <table>
    <tr>
      <td>Order #:</td>
      <td class="text-end fw-bold">{{ $order->order_number }}</td>
    </tr>
    <tr>
      <td>Date:</td>
      <td class="text-end">{{ $order->created_at->format('d/m/Y H:i') }}</td>
    </tr>
    @if ($order->table_name)
    <tr>
      <td>Table:</td>
      <td class="text-end fw-bold">{{ $order->table_name }}</td>
    </tr>
    @endif
    @if ($order->customer_name)
    <tr>
      <td>Customer:</td>
      <td class="text-end">{{ $order->customer_name }}</td>
    </tr>
    @endif
    @if ($order->customer_phone)
    <tr>
      <td>Phone:</td>
      <td class="text-end">{{ $order->customer_phone }}</td>
    </tr>
    @endif
    @if ($order->order_type === 'DELIVERY')
      @if ($order->deliveryArea)
      <tr>
        <td>Area:</td>
        <td class="text-end">{{ $order->deliveryArea->name }}</td>
      </tr>
      @endif
      @if ($order->rider)
      <tr>
        <td>Rider:</td>
        <td class="text-end">{{ $order->rider->name }} ({{ $order->rider->vehicle_number }})</td>
      </tr>
      @endif
      @if ($order->customer_address)
      <tr>
        <td colspan="2" style="font-size: 11px; padding-top: 3px;">
          <strong>Address:</strong> {{ $order->customer_address }}
        </td>
      </tr>
      @endif
    @endif
    @if ($order->cashier)
    <tr>
      <td>Cashier:</td>
      <td class="text-end">{{ $order->cashier->name }}</td>
    </tr>
    @endif
  </table>

  <div class="border-top my-1"></div>

  <table>
    <thead>
      <tr class="border-bottom">
        <th class="text-start">Item</th>
        <th class="text-center" style="width: 30px;">Qty</th>
        <th class="text-end" style="width: 50px;">Price</th>
        <th class="text-end" style="width: 60px;">Total</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($order->items as $item)
      <tr>
        <td class="text-start">
          {{ $item->product_name }}
          @php
            $dealModel = $item->deal ?? \App\Models\Deal::where('product_id', $item->product_id)->with('items.product')->first();
          @endphp
          @if ($dealModel && $dealModel->items->isNotEmpty())
            <div style="font-size: 10px; color: #333; margin-top: 2px;">
              @foreach ($dealModel->items as $dItem)
                <div>↳ {{ (int)($dItem->quantity * $item->quantity) }}x {{ $dItem->product?->name }}</div>
              @endforeach
            </div>
          @endif
          @if ($item->notes)
            <div style="font-size: 10px; font-style: italic;">* {{ $item->notes }}</div>
          @endif
        </td>
        <td class="text-center">{{ (int) $item->quantity == $item->quantity ? (int) $item->quantity : $item->quantity }}</td>
        <td class="text-end">{{ number_format($item->unit_price) }}</td>
        <td class="text-end">{{ number_format($item->subtotal) }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>

  <div class="border-top my-1"></div>

  <table>
    <tr>
      <td>Subtotal:</td>
      <td class="text-end">{{ $currency }} {{ number_format($order->subtotal, 2) }}</td>
    </tr>
    @if ($order->discount_amount > 0)
    <tr>
      <td>Discount:</td>
      <td class="text-end">- {{ $currency }} {{ number_format($order->discount_amount, 2) }}</td>
    </tr>
    @endif
    @if ($order->delivery_charge > 0)
    <tr>
      <td>Delivery Fee:</td>
      <td class="text-end">+ {{ $currency }} {{ number_format($order->delivery_charge, 2) }}</td>
    </tr>
    @endif
    @if ($order->tax_amount > 0)
    <tr>
      <td>Tax:</td>
      <td class="text-end">+ {{ $currency }} {{ number_format($order->tax_amount, 2) }}</td>
    </tr>
    @endif
    <tr class="fw-bold" style="font-size: 15px;">
      <td class="border-top py-1">TOTAL:</td>
      <td class="text-end border-top py-1">{{ $currency }} {{ number_format($order->grand_total, 2) }}</td>
    </tr>
    <tr>
      <td>Amount Paid:</td>
      <td class="text-end">{{ $currency }} {{ number_format($order->paid_amount, 2) }}</td>
    </tr>
    @if ($order->balance_amount > 0)
    <tr class="fw-bold">
      <td>Balance Due:</td>
      <td class="text-end">{{ $currency }} {{ number_format($order->balance_amount, 2) }}</td>
    </tr>
    @endif
  </table>

  <div class="border-top my-2 text-center py-1" style="font-size: 11px;">
    @if (! $order->isFinalized() || $order->payment_status !== 'paid')
      <div class="fw-bold">*** PLEASE PAY AT COUNTER / RIDER ***</div>
    @else
      <div>*** THANK YOU! ***</div>
    @endif
    <div>{{ $footerNote }}</div>
  </div>
  </div> <!-- .receipt-paper -->

  <script>
    window.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') {
        window.close();
      }
    });
  </script>
</body>
</html>
