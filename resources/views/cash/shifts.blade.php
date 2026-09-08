@extends('layouts.app', ['title' => 'Cash Drawer Shifts - Food Point POS'])

@section('content')
<div class="page-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
  <div>
    <h1 class="page-title">Cashier Shifts & Cash Drawer</h1>
    <nav class="breadcrumb small">
      <a href="{{ route('dashboard') }}" class="breadcrumb-item text-decoration-none">Home</a>
      <span class="breadcrumb-item active">Cash Shifts</span>
    </nav>
  </div>
  <div>
    <a href="{{ route('cash.day-close.index') }}" class="btn btn-outline-primary btn-sm px-3 py-2">
      <i class="bi bi-calendar-check me-1"></i> Day Close (Z-Report)
    </a>
  </div>
</div>

<!-- Active Shift Status Card -->
@if ($activeShift)
  <div class="card border border-success mb-4 shadow-sm">
    <div class="card-header bg-success-subtle py-3 d-flex align-items-center justify-content-between">
      <div class="d-flex align-items-center gap-2">
        <span class="badge bg-success">ACTIVE SHIFT #{{ $activeShift->id }}</span>
        <span class="small text-muted">Opened {{ $activeShift->opened_at->format('d M Y, h:i A') }}</span>
      </div>
      <div>
        <button type="button" class="btn btn-outline-primary btn-sm me-2" data-bs-toggle="modal" data-bs-target="#payInModal">
          <i class="bi bi-arrow-down-circle me-1"></i> Drawer Pay-In / Pay-Out
        </button>
        <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#closeShiftModal">
          <i class="bi bi-lock-fill me-1"></i> Close & Reconcile Shift
        </button>
      </div>
    </div>
    <div class="card-body">
      <div class="row g-3 text-center">
        <div class="col-sm-6 col-md-2">
          <div class="p-2 border rounded bg-light-subtle">
            <div class="small text-muted">Opening Float</div>
            <div class="fs-6 fw-bold text-heading">Rs. {{ number_format($activeShift->opening_cash) }}</div>
          </div>
        </div>
        <div class="col-sm-6 col-md-2">
          <div class="p-2 border rounded bg-light-subtle">
            <div class="small text-muted">+ Cash Sales</div>
            <div class="fs-6 fw-bold text-success">+ Rs. {{ number_format($activeShift->cash_sales) }}</div>
          </div>
        </div>
        <div class="col-sm-6 col-md-2">
          <div class="p-2 border rounded bg-light-subtle">
            <div class="small text-muted">+ Cash In</div>
            <div class="fs-6 fw-bold text-primary">+ Rs. {{ number_format($activeShift->cash_receipts) }}</div>
          </div>
        </div>
        <div class="col-sm-6 col-md-2">
          <div class="p-2 border rounded bg-light-subtle">
            <div class="small text-muted">- Cash Out</div>
            <div class="fs-6 fw-bold text-danger">- Rs. {{ number_format($activeShift->cash_payments) }}</div>
          </div>
        </div>
        <div class="col-sm-6 col-md-2">
          <div class="p-2 border rounded bg-light-subtle">
            <div class="small text-muted">- Refunds</div>
            <div class="fs-6 fw-bold text-danger">- Rs. {{ number_format($activeShift->refunds) }}</div>
          </div>
        </div>
        <div class="col-sm-6 col-md-2">
          <div class="p-2 border rounded bg-primary-subtle border-primary">
            <div class="small text-primary fw-bold">Expected In Drawer</div>
            <div class="fs-6 fw-bold text-primary">Rs. {{ number_format($activeShift->expected_cash) }}</div>
          </div>
        </div>
      </div>
    </div>
  </div>
@else
  <div class="alert alert-warning d-flex align-items-center justify-content-between p-3 mb-4 border">
    <div class="d-flex align-items-center gap-2">
      <i class="bi bi-exclamation-triangle-fill fs-4"></i>
      <div>
        <strong>No Active Cash Drawer Shift</strong>
        <div class="small">Open a shift to track cash collections, calculate drawer differences, and accept cash billing.</div>
      </div>
    </div>
    <button type="button" class="btn btn-primary btn-sm px-3 py-2 fw-semibold" data-bs-toggle="modal" data-bs-target="#openShiftModal">
      <i class="bi bi-key-fill me-1"></i> Open New Cash Shift
    </button>
  </div>
@endif

<!-- Past Shifts Table -->
<div class="card border">
  <div class="card-header bg-transparent py-3">
    <h5 class="card-title mb-0 fs-6 fw-bold">Cash Drawer Shift Reconciliation History</h5>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Shift #</th>
            <th>Cashier</th>
            <th>Opened At</th>
            <th>Closed At</th>
            <th>Opening Float</th>
            <th>Cash Sales</th>
            <th>Expected Cash</th>
            <th>Actual Counted</th>
            <th>Difference</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($pastShifts as $s)
            <tr>
              <td class="fw-bold">#{{ $s->id }}</td>
              <td>{{ $s->user?->name ?? 'Cashier' }}</td>
              <td class="small text-muted">{{ $s->opened_at->format('d M Y, h:i A') }}</td>
              <td class="small text-muted">{{ $s->closed_at ? $s->closed_at->format('d M Y, h:i A') : '-' }}</td>
              <td>Rs. {{ number_format($s->opening_cash) }}</td>
              <td class="text-success fw-semibold">Rs. {{ number_format($s->cash_sales) }}</td>
              <td class="fw-semibold">Rs. {{ number_format($s->expected_cash) }}</td>
              <td class="fw-bold">{{ $s->actual_cash !== null ? 'Rs. ' . number_format($s->actual_cash) : '-' }}</td>
              <td>
                @if ($s->difference !== null)
                  <span class="badge {{ $s->difference == 0 ? 'bg-success' : ($s->difference > 0 ? 'bg-info' : 'bg-danger') }}">
                    {{ $s->difference >= 0 ? '+' : '' }}Rs. {{ number_format($s->difference) }}
                  </span>
                @else
                  -
                @endif
              </td>
              <td>
                <span class="badge {{ $s->status === 'open' ? 'badge-soft-success' : 'badge-soft-secondary' }}">
                  {{ ucfirst($s->status) }}
                </span>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="10" class="text-center py-4 text-muted">No shifts logged yet.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
  @if ($pastShifts->hasPages())
    <div class="card-footer bg-transparent py-2">
      {{ $pastShifts->links() }}
    </div>
  @endif
</div>

<!-- OPEN SHIFT MODAL -->
<div class="modal fade" id="openShiftModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="{{ route('cash.shifts.open') }}">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title fw-bold">Open Cash Drawer Shift</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Opening Float Amount (Cash in Drawer)</label>
            <input type="number" name="opening_cash" class="form-control form-control-lg text-end fw-bold" value="5000" min="0" step="100" required>
            <div class="form-text small">Count coins and bills in the drawer before starting your shift.</div>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Shift Notes</label>
            <textarea name="notes" class="form-control" rows="2" placeholder="Optional notes..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Open Cash Shift</button>
        </div>
      </form>
    </div>
  </div>
</div>

@if ($activeShift)
<!-- CLOSE SHIFT MODAL -->
<div class="modal fade" id="closeShiftModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="{{ route('cash.shifts.close', $activeShift->id) }}">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title fw-bold">Close & Reconcile Shift #{{ $activeShift->id }}</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          @php
            $unpaidOrders = \App\Models\Order::where(function ($q) use ($activeShift) {
                $q->where('cash_shift_id', $activeShift->id)
                  ->orWhereNull('cash_shift_id');
            })
            ->where('payment_status', '!=', 'paid')
            ->where('order_status', '!=', 'cancelled')
            ->get();
          @endphp

          @if ($unpaidOrders->isNotEmpty())
            <div class="alert alert-danger py-2 px-3 small mb-3">
              <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i> Cannot Close Shift: {{ $unpaidOrders->count() }} Unpaid Order(s) Pending</div>
              <div>All active orders must be paid and settled before closing this shift.</div>
              <ul class="mb-1 mt-1 ps-3">
                @foreach ($unpaidOrders->take(3) as $uo)
                  <li><strong>#{{ $uo->order_number }}</strong> ({{ $uo->order_type }}) - Due: Rs. {{ number_format($uo->balance_amount) }}</li>
                @endforeach
              </ul>
              <a href="{{ route('orders.index') }}?payment_status=unpaid" class="btn btn-outline-danger btn-sm py-0 px-2 mt-1 fw-semibold" target="_blank">
                <i class="bi bi-box-arrow-up-right me-1"></i> View & Settle Unpaid Orders
              </a>
            </div>
          @else
            <div class="alert alert-success py-2 px-3 small mb-3">
              <i class="bi bi-check-circle-fill me-1"></i> All orders are settled and paid. Shift ready to close and reconcile.
            </div>
          @endif

          <div class="alert alert-info py-2 px-3 small mb-3">
            Expected Drawer Cash: <strong>Rs. {{ number_format($activeShift->expected_cash, 2) }}</strong>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Physical Counted Cash</label>
            <input type="number" name="actual_cash" class="form-control form-control-lg text-end fw-bold" placeholder="Enter counted amount" min="0" step="1" required {{ $unpaidOrders->isNotEmpty() ? 'disabled' : '' }}>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Handover / Shift Closing Notes</label>
            <textarea name="notes" class="form-control" rows="2" placeholder="Explain any cash overages or shortages..." {{ $unpaidOrders->isNotEmpty() ? 'disabled' : '' }}></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger" {{ $unpaidOrders->isNotEmpty() ? 'disabled' : '' }}>Confirm & Close Shift</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- PAY IN / PAY OUT MODAL -->
<div class="modal fade" id="payInModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="{{ route('cash.shifts.transaction', $activeShift->id) }}">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title fw-bold">Drawer Pay-In / Pay-Out</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Transaction Type</label>
            <select name="type" class="form-select" required>
              <option value="cash_in">Pay-In (Add Cash to Drawer)</option>
              <option value="cash_out">Pay-Out (Remove Cash from Drawer)</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Amount (Rs.)</label>
            <input type="number" name="amount" class="form-control text-end fw-bold" step="1" min="1" required>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Description / Purpose</label>
            <input type="text" name="description" class="form-control" placeholder="e.g. Added extra change, Owner personal drawing" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Transaction</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endif
@endsection
