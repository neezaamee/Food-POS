<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>KOT #{{ $targetKot ? $targetKot->kot_number : 1 }} - {{ $order->order_number }}</title>
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
    .text-center { text-align: center; }
    .text-end { text-align: right; }
    .text-start { text-align: left; }
    .fw-bold { font-weight: bold; }
    .border-top { border-top: 1px dashed #000; }
    .border-bottom { border-bottom: 1px dashed #000; }
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
    .badge-kot {
      display: inline-block;
      border: 2px solid #000;
      padding: 3px 8px;
      font-size: 16px;
      font-weight: 900;
      margin-top: 4px;
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
    <button onclick="window.print()" class="btn-action btn-primary">🖨️ Print KOT</button>
    <button onclick="window.close()" class="btn-action btn-secondary">✕ Close (Esc)</button>
  </div>

  <div class="receipt-paper">

  @php
    $kotNum = $targetKot ? $targetKot->kot_number : 1;
    $isIncremental = $targetKot && $targetKot->kot_number > 1;
  @endphp

  <div class="text-center">
    <h2 style="margin: 0; font-size: 18px; letter-spacing: 1px;">
      @if ($isIncremental)
        *** RUNNING KOT #{{ $kotNum }} ***
      @else
        *** KITCHEN ORDER TICKET ***
      @endif
    </h2>
    <div style="font-size: 15px; font-weight: bold; margin-top: 2px;">TYPE: {{ $order->order_type }}</div>
    
    <div class="badge-kot">
      KOT #{{ $kotNum }}
      @if ($isIncremental)
        (ADDITIONAL / RECALLED)
      @endif
    </div>
  </div>

  <div class="border-top border-bottom py-1 my-2">
    <table>
      <tr>
        <td>Order Number:</td>
        <td class="text-end fw-bold" style="font-size: 17px;">{{ $order->order_number }}</td>
      </tr>
      <tr>
        <td>KOT Batch:</td>
        <td class="text-end fw-bold" style="font-size: 15px;">TICKET #{{ $kotNum }}</td>
      </tr>
      @if ($order->table_name)
      <tr>
        <td class="fw-bold" style="font-size: 15px;">TABLE:</td>
        <td class="text-end fw-bold" style="font-size: 18px;">{{ $order->table_name }}</td>
      </tr>
      @endif
      <tr>
        <td>Time Placed:</td>
        <td class="text-end">{{ ($targetKot?->created_at ?? $order->created_at)->format('d/m/Y H:i') }}</td>
      </tr>
      @if ($order->customer_name)
      <tr>
        <td>Customer:</td>
        <td class="text-end fw-bold">{{ $order->customer_name }}</td>
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
          <td class="text-end fw-bold">{{ $order->deliveryArea->name }}</td>
        </tr>
        @endif
        @if ($order->customer_address)
        <tr>
          <td colspan="2" style="padding-top: 4px; font-size: 12px;">
            <strong>Drop-off:</strong> {{ $order->customer_address }}
          </td>
        </tr>
        @endif
      @endif
    </table>
  </div>

  @if ($isIncremental)
    <div style="background-color: #eee; padding: 4px; text-align: center; font-size: 12px; font-weight: bold; border: 1px dashed #000; margin-bottom: 6px;">
      NOTE: Prepare ONLY the items listed below!
    </div>
  @endif

  <table class="my-2">
    <thead>
      <tr class="border-bottom">
        <th class="text-start py-1" style="font-size: 14px; width: 45px;">QTY</th>
        <th class="text-start py-1" style="font-size: 14px;">ITEM (EN & URDU)</th>
      </tr>
    </thead>
    <tbody>
      @php
        $itemsToPrint = $targetKot ? $targetKot->items : $order->items;
      @endphp
      @foreach ($itemsToPrint as $item)
      <tr class="border-bottom">
        <td class="text-start py-2 fw-bold" style="font-size: 18px; vertical-align: top; width: 45px;">
          {{ (int)$item->quantity }}x
        </td>
        <td class="text-start py-2" style="font-size: 15px;">
          <!-- English Item Name -->
          <div class="fw-bold" style="font-size: 16px;">{{ $item->product_name }}</div>

          <!-- Urdu Item Name -->
          @php
            $urduName = $item->product_name_ur ?? $item->product?->name_ur;
          @endphp
          @if ($urduName)
            <div class="urdu-text">{{ $urduName }}</div>
          @endif

          <!-- Deal / Package sub-items if applicable -->
          @php
            $dealModel = \App\Models\Deal::where('product_id', $item->product_id)->with('items.product')->first();
          @endphp
          @if ($dealModel && $dealModel->items->isNotEmpty())
            <div style="font-size: 12px; margin-top: 2px; font-weight: bold; padding-left: 6px;">
              @foreach ($dealModel->items as $dItem)
                <div>
                  ↳ {{ (int)($dItem->quantity * $item->quantity) }}x {{ $dItem->product?->name }}
                  @if ($dItem->product?->name_ur)
                    <span class="urdu-text" style="font-size: 13px;">({{ $dItem->product->name_ur }})</span>
                  @endif
                </div>
              @endforeach
            </div>
          @endif

          <!-- Cooking Note -->
          @if ($item->notes)
            <div style="font-size: 13px; font-weight: bold; margin-top: 3px; background: #000; color: #fff; padding: 2px 4px; display: inline-block;">
              *** NOTE: {{ $item->notes }} ***
            </div>
          @endif
        </td>
      </tr>
      @endforeach
    </tbody>
  </table>

  @if ($targetKot?->notes || $order->notes)
    <div class="border-top py-1 my-1" style="font-size: 13px;">
      <strong>SPECIAL INSTRUCTIONS:</strong><br>
      {{ $targetKot?->notes ?: $order->notes }}
    </div>
  @endif

  <div class="border-top my-2 text-center py-1" style="font-size: 11px;">
    <div>--- END OF KOT #{{ $kotNum }} ---</div>
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
