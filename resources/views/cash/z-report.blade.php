<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Z-Report - {{ $dayClose->business_date->format('Y-m-d') }} - Food Point POS</title>
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
      line-height: 1.35;
      margin: 0 auto;
      padding: 10px;
      color: #000;
      max-width: 80mm;
      box-sizing: border-box;
      background: #fff;
    }
    .text-center { text-align: center; }
    .text-end { text-align: right; }
    .text-start { text-align: left; }
    .fw-bold { font-weight: bold; }
    .border-top { border-top: 1px dashed #000; }
    .border-bottom { border-bottom: 1px dashed #000; }
    .border-double { border-top: 2px solid #000; border-bottom: 2px solid #000; }
    .my-1 { margin-top: 4px; margin-bottom: 4px; }
    .my-2 { margin-top: 8px; margin-bottom: 8px; }
    .py-1 { padding-top: 4px; padding-bottom: 4px; }
    .py-2 { padding-top: 8px; padding-bottom: 8px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { font-size: 12px; vertical-align: top; }
    .no-print {
      display: flex;
      justify-content: center;
      gap: 10px;
      margin-bottom: 16px;
      padding: 10px;
      background: #f8f9fa;
      border-radius: 6px;
      border: 1px solid #ddd;
    }
    .no-print button, .no-print a {
      font-family: system-ui, -apple-system, sans-serif;
      font-size: 13px;
      padding: 6px 14px;
      border-radius: 4px;
      border: 1px solid #ccc;
      cursor: pointer;
      text-decoration: none;
      color: #333;
      background: #fff;
    }
    .no-print .btn-print {
      background: #0d6efd;
      color: #fff;
      border-color: #0d6efd;
      font-weight: bold;
    }
    @media print {
      .no-print { display: none !important; }
      body { width: 100%; max-width: 100%; padding: 0; }
    }
  </style>
</head>
<body>
  <div class="no-print">
    <button onclick="window.print()" class="btn-print">🖨️ Print Z-Report</button>
    <a href="{{ route('cash.day-close.index', ['date' => $dayClose->business_date->format('Y-m-d')]) }}">⬅️ Back to Day Close</a>
  </div>

  @php
    $restName = \App\Models\SystemSetting::get('restaurant_name', 'FOOD POINT RESTAURANT');
    $tagline = \App\Models\SystemSetting::get('tagline', 'Modern POS & Dining ERP');
    $address = \App\Models\SystemSetting::get('restaurant_address', 'D-Ground, Faisalabad');
    $phone = \App\Models\SystemSetting::get('restaurant_phone', '');
    $ntn = \App\Models\SystemSetting::get('ntn_number', '');
    $strn = \App\Models\SystemSetting::get('strn_number', '');
  @endphp

  <!-- Header -->
  <div class="text-center">
    <h2 style="margin: 0; font-size: 18px; text-transform: uppercase;">{{ $restName }}</h2>
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
        @if ($strn) STRN: {{ $strn }} @endif
      </div>
    @endif

    <div class="my-2 border-top border-bottom py-1">
      <div class="fw-bold" style="font-size: 15px; letter-spacing: 1px;">*** DAILY Z-REPORT ***</div>
      <div style="font-size: 11px;">END OF DAY MASTER SETTLEMENT</div>
    </div>
  </div>

  <!-- Settlement Metadata -->
  <table class="my-1">
    <tr>
      <td>Business Date:</td>
      <td class="text-end fw-bold">{{ $dayClose->business_date->format('D, d M Y') }}</td>
    </tr>
    <tr>
      <td>Closed Time:</td>
      <td class="text-end">{{ $dayClose->closed_at->format('d/m/Y h:i A') }}</td>
    </tr>
    <tr>
      <td>Settled By:</td>
      <td class="text-end fw-bold">{{ $dayClose->closedByUser?->name ?? 'Manager' }}</td>
    </tr>
    <tr>
      <td>Combined Shifts:</td>
      <td class="text-end fw-bold">{{ $dayClose->total_shifts_count }} Shifts</td>
    </tr>
    <tr>
      <td>Total Orders:</td>
      <td class="text-end fw-bold">{{ $dayClose->total_orders_count }} Completed</td>
    </tr>
  </table>

  <!-- Shifts Detail (Shift 1, Shift 2) -->
  <div class="border-top my-2 py-1">
    <div class="fw-bold text-center" style="font-size: 11px;">--- SHIFTS BREAKDOWN ---</div>
    <table>
      <thead>
        <tr class="border-bottom">
          <th class="text-start">Shift / Cashier</th>
          <th class="text-end">Timing</th>
          <th class="text-end">Cash Sales</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($shifts as $s)
          <tr>
            <td>
              <strong>#{{ $s->id }}</strong>: {{ $s->user?->name ?? 'User' }}
              @if ($s->notes)
                <div style="font-size: 10px; color: #555;">{{ $s->notes }}</div>
              @endif
            </td>
            <td class="text-end">
              {{ $s->opened_at->format('H:i') }}-{{ $s->closed_at ? $s->closed_at->format('H:i') : 'Act' }}
            </td>
            <td class="text-end fw-bold">
              Rs. {{ number_format($s->cash_sales, 2) }}
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>

  <!-- Financial Sales Summary -->
  <div class="border-top my-2 py-1">
    <div class="fw-bold text-center" style="font-size: 11px;">--- SALES REVENUE SUMMARY ---</div>
    <table>
      <tr>
        <td>Gross Food Sales:</td>
        <td class="text-end">Rs. {{ number_format($dayClose->gross_sales, 2) }}</td>
      </tr>
      @if ($dayClose->discount_amount > 0)
        <tr>
          <td>Total Discounts:</td>
          <td class="text-end">- Rs. {{ number_format($dayClose->discount_amount, 2) }}</td>
        </tr>
      @endif
      @if ($dayClose->tax_amount > 0)
        <tr>
          <td>Sales Tax (PRA/GST):</td>
          <td class="text-end">+ Rs. {{ number_format($dayClose->tax_amount, 2) }}</td>
        </tr>
      @endif
      @if ($dayClose->delivery_charges > 0)
        <tr>
          <td>Delivery Charges:</td>
          <td class="text-end">+ Rs. {{ number_format($dayClose->delivery_charges, 2) }}</td>
        </tr>
      @endif
      <tr class="border-top">
        <td class="fw-bold" style="font-size: 14px;">NET TOTAL SALES:</td>
        <td class="text-end fw-bold" style="font-size: 14px;">Rs. {{ number_format($dayClose->net_sales, 2) }}</td>
      </tr>
    </table>
  </div>

  <!-- Payment Methods Breakdown -->
  <div class="border-top my-2 py-1">
    <div class="fw-bold text-center" style="font-size: 11px;">--- REVENUE BY PAYMENT TYPE ---</div>
    <table>
      <tr>
        <td>Cash Payments:</td>
        <td class="text-end fw-bold">Rs. {{ number_format($dayClose->cash_sales, 2) }}</td>
      </tr>
      <tr>
        <td>Card / Digital / Bank:</td>
        <td class="text-end fw-bold">Rs. {{ number_format($dayClose->digital_sales, 2) }}</td>
      </tr>
      <tr>
        <td>Credit / Customer AR:</td>
        <td class="text-end fw-bold">Rs. {{ number_format($dayClose->credit_sales, 2) }}</td>
      </tr>
      @if ($dayClose->total_refunds > 0)
        <tr>
          <td>Refunds Paid Out:</td>
          <td class="text-end text-danger">- Rs. {{ number_format($dayClose->total_refunds, 2) }}</td>
        </tr>
      @endif
    </table>
  </div>

  <!-- Cash Drawer Reconciliation -->
  <div class="border-top my-2 py-1">
    <div class="fw-bold text-center" style="font-size: 11px;">--- CASH DRAWER AUDIT ---</div>
    <table>
      <tr>
        <td>Opening Floats Combined:</td>
        <td class="text-end">Rs. {{ number_format($dayClose->opening_cash_total, 2) }}</td>
      </tr>
      <tr>
        <td>Expected Cash in Drawers:</td>
        <td class="text-end fw-bold">Rs. {{ number_format($dayClose->expected_cash_total, 2) }}</td>
      </tr>
      <tr>
        <td>Actual Counted Cash:</td>
        <td class="text-end fw-bold">Rs. {{ number_format($dayClose->actual_cash_total, 2) }}</td>
      </tr>
      <tr class="border-top">
        <td class="fw-bold">OVER / SHORT DIFFERENCE:</td>
        <td class="text-end fw-bold">
          {{ $dayClose->difference_total >= 0 ? '+' : '' }}Rs. {{ number_format($dayClose->difference_total, 2) }}
        </td>
      </tr>
    </table>
  </div>

  @if ($dayClose->notes)
    <div class="border-top my-2 py-1">
      <div class="fw-bold small">Manager Notes:</div>
      <div style="font-size: 11px;">{{ $dayClose->notes }}</div>
    </div>
  @endif

  <!-- Signatures -->
  <div class="border-top my-3 pt-3">
    <br><br>
    <table>
      <tr>
        <td style="width: 50%;" class="text-center">
          ___________________<br>
          <span style="font-size: 10px;">Shift Supervisor</span>
        </td>
        <td style="width: 50%;" class="text-center">
          ___________________<br>
          <span style="font-size: 10px;">Manager / Auditor</span>
        </td>
      </tr>
    </table>
  </div>

  <div class="text-center border-top pt-2 my-2" style="font-size: 10px;">
    <div>*** END OF Z-REPORT ***</div>
    <div>Food Point POS ERP &bull; Double-Entry Verified</div>
  </div>
</body>
</html>
