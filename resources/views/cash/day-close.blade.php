@extends('layouts.app', ['title' => 'Day Close (Z-Report Settlement) - Food Point POS'])

@section('content')
<div class="page-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
  <div>
    <h1 class="page-title">Day Close & Daily Master Settlement</h1>
    <nav class="breadcrumb small">
      <a href="{{ route('dashboard') }}" class="breadcrumb-item text-decoration-none">Home</a>
      <a href="{{ route('cash.shifts') }}" class="breadcrumb-item text-decoration-none">Cash Shifts</a>
      <span class="breadcrumb-item active">Day Close</span>
    </nav>
  </div>
  <div class="d-flex align-items-center gap-2">
    <form method="GET" action="{{ route('cash.day-close.index') }}" class="d-flex align-items-center gap-2">
      <label class="small text-muted fw-bold mb-0">Date:</label>
      <input type="date" name="date" class="form-control form-control-sm" value="{{ $selectedDate }}" onchange="this.form.submit()">
    </form>
  </div>
</div>

@if ($existingDayClose)
  <div class="alert alert-success d-flex align-items-center justify-content-between p-3 mb-4 shadow-sm">
    <div class="d-flex align-items-center gap-3">
      <div class="fs-2 text-success"><i class="bi bi-check-circle-fill"></i></div>
      <div>
        <h5 class="fw-bold mb-1">Day Close Completed for {{ $existingDayClose->business_date->format('M d, Y') }}</h5>
        <div class="small text-muted">
          Settled by <strong>{{ $existingDayClose->closedByUser?->name ?? 'System' }}</strong> on {{ $existingDayClose->closed_at->format('M d, Y h:i A') }} &bull; Combined Net Sales: <strong>Rs. {{ number_format($existingDayClose->net_sales, 2) }}</strong> across {{ $existingDayClose->total_shifts_count }} shifts.
        </div>
      </div>
    </div>
    <a href="{{ route('cash.day-close.z-report', $existingDayClose->id) }}" class="btn btn-success fw-bold px-3 py-2">
      <i class="bi bi-printer me-1"></i> View / Print Z-Report
    </a>
  </div>
@endif

<!-- Consolidated KPI Cards for Selected Date -->
<div class="row g-3 mb-4">
  <div class="col-sm-6 col-lg-3">
    <div class="card border shadow-sm h-100">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="text-muted small text-uppercase fw-bold">Combined Net Sales</div>
            <h3 class="fw-bold mb-0 text-primary mt-1">Rs. {{ number_format($netSales, 2) }}</h3>
          </div>
          <div class="bg-primary-subtle text-primary p-2 rounded">
            <i class="bi bi-cash-stack fs-4"></i>
          </div>
        </div>
        <div class="small text-muted mt-2">
          <span>Gross: Rs. {{ number_format($grossSales, 2) }}</span>
          @if ($totalDiscount > 0)
            <span class="text-danger ms-1">(-{{ number_format($totalDiscount, 2) }})</span>
          @endif
        </div>
      </div>
    </div>
  </div>

  <div class="col-sm-6 col-lg-3">
    <div class="card border shadow-sm h-100">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="text-muted small text-uppercase fw-bold">Cash in Drawers</div>
            <h3 class="fw-bold mb-0 text-success mt-1">Rs. {{ number_format($cashSales, 2) }}</h3>
          </div>
          <div class="bg-success-subtle text-success p-2 rounded">
            <i class="bi bi-wallet2 fs-4"></i>
          </div>
        </div>
        <div class="small text-muted mt-2">
          <span>Total Opening Floats: Rs. {{ number_format($openingCashTotal, 2) }}</span>
        </div>
      </div>
    </div>
  </div>

  <div class="col-sm-6 col-lg-3">
    <div class="card border shadow-sm h-100">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="text-muted small text-uppercase fw-bold">Card & Digital Sales</div>
            <h3 class="fw-bold mb-0 text-info mt-1">Rs. {{ number_format($digitalSales, 2) }}</h3>
          </div>
          <div class="bg-info-subtle text-info p-2 rounded">
            <i class="bi bi-credit-card fs-4"></i>
          </div>
        </div>
        <div class="small text-muted mt-2">
          <span>Credit / AR: Rs. {{ number_format($creditSales, 2) }}</span>
        </div>
      </div>
    </div>
  </div>

  <div class="col-sm-6 col-lg-3">
    <div class="card border shadow-sm h-100">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="text-muted small text-uppercase fw-bold">Orders & Shifts</div>
            <h3 class="fw-bold mb-0 text-dark mt-1">{{ $completedOrders->count() }} <span class="fs-6 fw-normal text-muted">Orders</span></h3>
          </div>
          <div class="bg-dark-subtle text-dark p-2 rounded">
            <i class="bi bi-receipt-cutoff fs-4"></i>
          </div>
        </div>
        <div class="small text-muted mt-2">
          <span>{{ $shifts->count() }} Cashier Shift(s) on record</span>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Shifts Breakdown for Selected Date -->
<div class="card border shadow-sm mb-4">
  <div class="card-header bg-transparent py-3 d-flex align-items-center justify-content-between">
    <h5 class="fw-bold mb-0">
      <i class="bi bi-layers-half text-primary me-2"></i>Cashier Shifts for {{ \Carbon\Carbon::parse($selectedDate)->format('M d, Y') }}
    </h5>
    <div>
      @if ($hasOpenShifts)
        <span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle me-1"></i> Open Shift in Progress</span>
      @elseif ($shifts->isNotEmpty())
        <span class="badge bg-success"><i class="bi bi-check2-all me-1"></i> All Shifts Closed</span>
      @else
        <span class="badge bg-secondary">No Shifts</span>
      @endif
    </div>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Shift ID</th>
            <th>Cashier / User</th>
            <th>Timing</th>
            <th class="text-center">Status</th>
            <th class="text-end">Opening Float</th>
            <th class="text-end">Cash Sales</th>
            <th class="text-end">Expected Cash</th>
            <th class="text-end">Actual Counted</th>
            <th class="text-end">Difference (Over/Short)</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($shifts as $s)
            <tr>
              <td class="fw-bold text-primary">Shift #{{ $s->id }}</td>
              <td>
                <div class="fw-semibold">{{ $s->user?->name ?? 'Front Counter' }}</div>
                <div class="small text-muted">{{ $s->user?->email }}</div>
              </td>
              <td>
                <div class="small">Opened: {{ $s->opened_at->format('h:i A') }}</div>
                @if ($s->closed_at)
                  <div class="small text-muted">Closed: {{ $s->closed_at->format('h:i A') }}</div>
                @else
                  <div class="small text-warning fw-bold">Active Now</div>
                @endif
              </td>
              <td class="text-center">
                @if ($s->isOpen())
                  <span class="badge bg-warning text-dark">OPEN</span>
                @else
                  <span class="badge bg-success">CLOSED</span>
                @endif
              </td>
              <td class="text-end">Rs. {{ number_format($s->opening_cash, 2) }}</td>
              <td class="text-end fw-semibold text-success">+ Rs. {{ number_format($s->cash_sales, 2) }}</td>
              <td class="text-end fw-bold">Rs. {{ number_format($s->expected_cash, 2) }}</td>
              <td class="text-end fw-bold">
                @if ($s->actual_cash !== null)
                  Rs. {{ number_format($s->actual_cash, 2) }}
                @else
                  <span class="text-muted small">Not Counted</span>
                @endif
              </td>
              <td class="text-end">
                @if ($s->difference !== null)
                  <span class="badge {{ $s->difference == 0 ? 'bg-success' : ($s->difference > 0 ? 'bg-info' : 'bg-danger') }} fs-6">
                    {{ $s->difference >= 0 ? '+' : '' }}Rs. {{ number_format($s->difference, 2) }}
                  </span>
                @else
                  <span class="text-muted small">-</span>
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="9" class="text-center py-4 text-muted">
                No cashier shifts were opened or recorded for {{ $selectedDate }}.
              </td>
            </tr>
          @endforelse
        </tbody>
        @if ($shifts->isNotEmpty())
          <tfoot class="table-light fw-bold">
            <tr>
              <td colspan="4" class="text-end">Combined Shifts Totals:</td>
              <td class="text-end">Rs. {{ number_format($openingCashTotal, 2) }}</td>
              <td class="text-end text-success">Rs. {{ number_format($shifts->sum('cash_sales'), 2) }}</td>
              <td class="text-end">Rs. {{ number_format($expectedCashTotal, 2) }}</td>
              <td class="text-end">Rs. {{ number_format($actualCashTotal, 2) }}</td>
              <td class="text-end">
                <span class="badge {{ $differenceTotal == 0 ? 'bg-success' : ($differenceTotal > 0 ? 'bg-info' : 'bg-danger') }} fs-6">
                  {{ $differenceTotal >= 0 ? '+' : '' }}Rs. {{ number_format($differenceTotal, 2) }}
                </span>
              </td>
            </tr>
          </tfoot>
        @endif
      </table>
    </div>
  </div>
</div>

<!-- Perform Day Close Action Box -->
@if (!$existingDayClose)
  <div class="card border shadow-sm mb-4">
    <div class="card-header bg-light py-3">
      <h5 class="fw-bold mb-0 text-heading">
        <i class="bi bi-shield-lock text-primary me-2"></i>Execute Daily Master Settlement (Day Close)
      </h5>
    </div>
    <div class="card-body p-4">
      @if ($shifts->isEmpty())
        <div class="alert alert-secondary mb-0">
          <i class="bi bi-info-circle me-1"></i> No shifts were recorded for {{ $selectedDate }}. Day Close cannot be performed without recorded shifts.
        </div>
      @elseif ($hasOpenShifts)
        <div class="alert alert-warning mb-0">
          <h6 class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> Active Cash Shifts Detected!</h6>
          <p class="mb-0 small">
            One or more shifts are still currently open. To maintain financial integrity, all individual shifts (e.g. Shift 1, Shift 2) must be reconciled and closed by cashiers before the Day Close Master Settlement can proceed.
          </p>
          <div class="mt-2">
            <a href="{{ route('cash.shifts') }}" class="btn btn-warning btn-sm fw-bold">
              <i class="bi bi-arrow-right me-1"></i> Go to Cash Shifts to Close Active Shift
            </a>
          </div>
        </div>
      @else
        <form method="POST" action="{{ route('cash.day-close.store') }}" onsubmit="return confirm('Are you sure you want to perform Day Close for {{ $selectedDate }}? This will combine and finalize all shift sales and generate today\'s Z-Report.');">
          @csrf
          <input type="hidden" name="date" value="{{ $selectedDate }}">

          <p class="text-muted small mb-3">
            Performing Day Close will lock the operating date, combine Shift 1 and Shift 2 revenues into a single consolidated daily total, calculate net drawer discrepancy, and generate the official <strong>Z-Report (Daily Master Settlement)</strong>.
          </p>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Manager Settlement Notes (Optional)</label>
            <textarea name="notes" class="form-control" rows="2" placeholder="e.g. Both Shift 1 and Shift 2 reconciled smoothly; cash deposited into safe."></textarea>
          </div>

          <button type="submit" class="btn btn-primary fw-bold px-4 py-2">
            <i class="bi bi-check2-circle me-1"></i> Settle Day Close & Generate Z-Report
          </button>
        </form>
      @endif
    </div>
  </div>
@endif

<!-- Past Day Closes History Table -->
<div class="card border shadow-sm mb-4">
  <div class="card-header bg-transparent py-3">
    <h5 class="fw-bold mb-0 text-heading">
      <i class="bi bi-clock-history text-secondary me-2"></i>Past Day Close Settlements (Z-Reports History)
    </h5>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Business Date</th>
            <th>Closed By</th>
            <th class="text-center">Shifts Combined</th>
            <th class="text-center">Total Orders</th>
            <th class="text-end">Net Sales</th>
            <th class="text-end">Cash Drawer Total</th>
            <th class="text-end">Discrepancy</th>
            <th class="text-end pe-3">Z-Report</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($pastDayCloses as $dc)
            <tr>
              <td class="fw-bold">
                <a href="{{ route('cash.day-close.index', ['date' => $dc->business_date->format('Y-m-d')]) }}" class="text-decoration-none">
                  {{ $dc->business_date->format('M d, Y') }}
                </a>
              </td>
              <td>{{ $dc->closedByUser?->name ?? 'Manager' }}</td>
              <td class="text-center"><span class="badge bg-secondary">{{ $dc->total_shifts_count }} Shifts</span></td>
              <td class="text-center">{{ $dc->total_orders_count }}</td>
              <td class="text-end fw-bold text-primary">Rs. {{ number_format($dc->net_sales, 2) }}</td>
              <td class="text-end">Rs. {{ number_format($dc->actual_cash_total, 2) }}</td>
              <td class="text-end">
                <span class="badge {{ $dc->difference_total == 0 ? 'bg-success' : ($dc->difference_total > 0 ? 'bg-info' : 'bg-danger') }}">
                  {{ $dc->difference_total >= 0 ? '+' : '' }}Rs. {{ number_format($dc->difference_total, 2) }}
                </span>
              </td>
              <td class="text-end pe-3">
                <a href="{{ route('cash.day-close.z-report', $dc->id) }}" class="btn btn-sm btn-outline-primary">
                  <i class="bi bi-receipt me-1"></i> Z-Report
                </a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="8" class="text-center py-4 text-muted">No past Day Close settlements recorded yet.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
  @if ($pastDayCloses->hasPages())
    <div class="card-footer bg-transparent py-2">
      {{ $pastDayCloses->links() }}
    </div>
  @endif
</div>
@endsection
