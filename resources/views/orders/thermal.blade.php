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
      font-family: 'Courier New', Courier, monospace, 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq';
      font-size: 14px;
      font-weight: 600;
      line-height: 1.35;
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
    .border-top { border-top: 1.5px dashed #000; }
    .border-bottom { border-bottom: 1.5px dashed #000; }
    .border-thick { border-top: 2px dashed #000; border-bottom: 2px dashed #000; }
    .my-1 { margin-top: 4px; margin-bottom: 4px; }
    .my-2 { margin-top: 8px; margin-bottom: 8px; }
    .py-1 { padding-top: 4px; padding-bottom: 4px; }
    table { width: 100%; border-collapse: collapse; }
    .urdu-text {
      font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', 'Urdu Typesetting', Tahoma, sans-serif;
      font-size: 15px;
      font-weight: bold;
      direction: rtl;
      text-align: right;
      line-height: 1.5;
    }
    .badge-receipt {
      display: inline-block;
      border: 2px solid #000;
      padding: 3px 8px;
      font-size: 15px;
      font-weight: 900;
      margin: 4px 0;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
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
        font-family: 'Courier New', Courier, monospace, 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq' !important;
        color: #000 !important;
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
    <h2 style="margin: 0; font-size: 19px; font-weight: 900; letter-spacing: 0.5px; text-transform: uppercase;">{{ $restName }}</h2>
    @if ($tagline)
      <div style="font-size: 12px; font-weight: bold;">{{ $tagline }}</div>
    @endif
    @if ($address)
      <div style="font-size: 12px;">{{ $address }}</div>
    @endif
    @if ($phone)
      <div style="font-size: 12px; font-weight: bold;">Tel: {{ $phone }}</div>
    @endif
    @if ($ntn || $strn)
      <div style="font-size: 11px;">
        @if ($ntn) NTN: {{ $ntn }} @endif
        @if ($strn) | STRN: {{ $strn }} @endif
      </div>
    @endif
  </div>

  <div class="text-center my-2">
    @if (! $order->isFinalized() || $order->payment_status !== 'paid')
      <div class="badge-receipt">
        *** {{ $order->order_type }} - UNPAID BILL ***
      </div>
      <div style="font-size: 13px; font-weight: 900;">(PAYMENT PENDING)</div>
    @else
      <div class="badge-receipt">
        *** {{ $order->order_type }} RECEIPT ***
      </div>
    @endif
  </div>

  <div class="border-top border-bottom py-1 my-2">
    <table>
      <tr>
        <td style="font-size: 14px;">Order #:</td>
        <td class="text-end fw-bold" style="font-size: 17px;">{{ $order->order_number }}</td>
      </tr>
      <tr>
        <td style="font-size: 13px;">Date:</td>
        <td class="text-end" style="font-size: 13px;">{{ $order->created_at->format('d/m/Y H:i') }}</td>
      </tr>
      @if ($order->table_name)
      <tr>
        <td class="fw-bold" style="font-size: 15px;">Table:</td>
        <td class="text-end fw-bold" style="font-size: 18px;">{{ $order->table_name }}</td>
      </tr>
      @endif
      @if ($order->customer_name)
      <tr>
        <td style="font-size: 13px;">Customer:</td>
        <td class="text-end fw-bold" style="font-size: 14px;">{{ $order->customer_name }}</td>
      </tr>
      @endif
      @if ($order->customer_phone)
      <tr>
        <td style="font-size: 13px;">Phone:</td>
        <td class="text-end fw-bold" style="font-size: 14px;">{{ $order->customer_phone }}</td>
      </tr>
      @endif
      @if ($order->order_type === 'DELIVERY')
        @if ($order->deliveryArea)
        <tr>
          <td style="font-size: 13px;">Area:</td>
          <td class="text-end fw-bold" style="font-size: 14px;">{{ $order->deliveryArea->name }}</td>
        </tr>
        @endif
        @if ($order->rider)
        <tr>
          <td style="font-size: 13px;">Rider:</td>
          <td class="text-end fw-bold" style="font-size: 14px;">{{ $order->rider->name }} ({{ $order->rider->vehicle_number }})</td>
        </tr>
        @endif
        @if ($order->customer_address)
        <tr>
          <td colspan="2" style="font-size: 13px; font-weight: bold; padding-top: 3px;">
            <strong>Drop-off Address:</strong> {{ $order->customer_address }}
          </td>
        </tr>
        @endif
      @endif
      @if ($order->cashier)
      <tr>
        <td style="font-size: 13px;">Cashier:</td>
        <td class="text-end" style="font-size: 13px;">{{ $order->cashier->name }}</td>
      </tr>
      @endif
    </table>
  </div>

  <table class="my-1">
    <thead>
      <tr class="border-bottom">
        <th class="text-start py-1" style="font-size: 14px; font-weight: bold;">ITEM</th>
        <th class="text-center py-1" style="width: 38px; font-size: 14px; font-weight: bold;">QTY</th>
        <th class="text-end py-1" style="width: 55px; font-size: 14px; font-weight: bold;">PRICE</th>
        <th class="text-end py-1" style="width: 65px; font-size: 14px; font-weight: bold;">TOTAL</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($order->items as $item)
      <tr class="border-bottom">
        <td class="text-start py-2">
          <div class="fw-bold" style="font-size: 15px;">{{ $item->product_name }}</div>
          @php
            $urduName = $item->product_name_ur ?? $item->product?->name_ur;
          @endphp
          @if ($urduName)
            <div class="urdu-text">{{ $urduName }}</div>
          @endif
          @php
            $dealModel = $item->deal ?? \App\Models\Deal::where('product_id', $item->product_id)->with('items.product')->first();
          @endphp
          @if ($dealModel && $dealModel->items->isNotEmpty())
            <div style="font-size: 12px; font-weight: bold; margin-top: 2px;">
              @foreach ($dealModel->items as $dItem)
                <div>↳ {{ (int)($dItem->quantity * $item->quantity) }}x {{ $dItem->product?->name }}</div>
              @endforeach
            </div>
          @endif
          @if ($item->notes)
            <div style="font-size: 12px; font-weight: bold; background: #000; color: #fff; padding: 1px 4px; display: inline-block; margin-top: 2px;">
              * {{ $item->notes }}
            </div>
          @endif
        </td>
        <td class="text-center py-2 fw-bold" style="font-size: 17px; vertical-align: top;">
          {{ (int) $item->quantity == $item->quantity ? (int) $item->quantity : $item->quantity }}
        </td>
        <td class="text-end py-2" style="font-size: 14px; vertical-align: top;">{{ number_format($item->unit_price) }}</td>
        <td class="text-end py-2 fw-bold" style="font-size: 15px; vertical-align: top;">{{ number_format($item->subtotal) }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>

  <div class="border-top my-1"></div>

  <table>
    <tr>
      <td style="font-size: 14px;">Subtotal:</td>
      <td class="text-end fw-bold" style="font-size: 14px;">{{ $currency }} {{ number_format($order->subtotal, 2) }}</td>
    </tr>
    @if ($order->discount_amount > 0)
    <tr>
      <td style="font-size: 14px;">Discount:</td>
      <td class="text-end fw-bold" style="font-size: 14px;">- {{ $currency }} {{ number_format($order->discount_amount, 2) }}</td>
    </tr>
    @endif
    @if ($order->delivery_charge > 0)
    <tr>
      <td style="font-size: 14px;">Delivery Fee:</td>
      <td class="text-end fw-bold" style="font-size: 14px;">+ {{ $currency }} {{ number_format($order->delivery_charge, 2) }}</td>
    </tr>
    @endif
    @if ($order->tax_amount > 0)
    <tr>
      <td style="font-size: 14px;">Tax:</td>
      <td class="text-end fw-bold" style="font-size: 14px;">+ {{ $currency }} {{ number_format($order->tax_amount, 2) }}</td>
    </tr>
    @endif
    <tr class="fw-bold border-thick" style="font-size: 20px;">
      <td class="py-1">TOTAL:</td>
      <td class="text-end py-1">{{ $currency }} {{ number_format($order->grand_total, 2) }}</td>
    </tr>
    <tr>
      <td style="font-size: 14px; padding-top: 4px;">Amount Paid:</td>
      <td class="text-end fw-bold" style="font-size: 15px; padding-top: 4px;">{{ $currency }} {{ number_format($order->paid_amount, 2) }}</td>
    </tr>
    @if ($order->payments->isNotEmpty())
      @foreach ($order->payments as $pmt)
        <tr>
          <td style="font-size: 12px; color: #333;">Paid via {{ ucfirst($pmt->payment_method) }}:</td>
          <td class="text-end" style="font-size: 12px;">
            {{ $currency }} {{ number_format($pmt->amount, 2) }}
            @if ($pmt->payment_reference)
              <div style="font-size: 10px; font-weight: bold;">TID: {{ $pmt->payment_reference }}</div>
            @endif
          </td>
        </tr>
      @endforeach
    @endif
    @if ($order->balance_amount > 0)
    <tr class="fw-bold" style="font-size: 16px;">
      <td>Balance Due:</td>
      <td class="text-end">{{ $currency }} {{ number_format($order->balance_amount, 2) }}</td>
    </tr>
    @endif
  </table>

  <div class="border-top my-2 text-center py-1">
    @if (! $order->isFinalized() || $order->payment_status !== 'paid')
      <div class="fw-bold" style="font-size: 14px; letter-spacing: 0.5px;">*** PLEASE PAY AT COUNTER / RIDER ***</div>
    @else
      <div class="fw-bold" style="font-size: 14px;">*** THANK YOU! ***</div>
    @endif
    <div style="font-size: 12px; margin-top: 4px;">{{ $footerNote }}</div>
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
