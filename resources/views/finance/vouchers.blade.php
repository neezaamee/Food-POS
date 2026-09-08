@extends('layouts.app', ['title' => 'Journal Vouchers - Food Point POS'])

@section('content')
<div class="page-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
  <div>
    <h1 class="page-title">General Journal Vouchers (JV)</h1>
    <nav class="breadcrumb small">
      <a href="{{ route('dashboard') }}" class="breadcrumb-item text-decoration-none">Home</a>
      <a href="{{ route('finance.chart-of-accounts') }}" class="breadcrumb-item text-decoration-none">Finance</a>
      <span class="breadcrumb-item active">Journal Vouchers</span>
    </nav>
  </div>
  <div>
    <button type="button" class="btn btn-primary btn-sm px-3 py-2" data-bs-toggle="modal" data-bs-target="#addVoucherModal">
      <i class="bi bi-plus-circle me-1"></i> New Journal Entry
    </button>
  </div>
</div>

<div class="card border">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Voucher #</th>
            <th>Date</th>
            <th>Type</th>
            <th>Narration</th>
            <th>Debit Lines</th>
            <th>Credit Lines</th>
            <th class="text-end">Total Amount</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($entries as $e)
            <tr>
              <td class="fw-bold">{{ $e->entry_number }}</td>
              <td class="small text-muted">{{ $e->entry_date->format('d M Y') }}</td>
              <td><span class="badge bg-secondary">{{ strtoupper($e->voucher_type) }}</span></td>
              <td class="small">{{ $e->notes }}</td>
              <td class="small">
                @foreach ($e->lines->where('debit', '>', 0) as $dl)
                  <div>Dr: {{ $dl->account->name }} (Rs. {{ number_format($dl->debit) }})</div>
                @endforeach
              </td>
              <td class="small">
                @foreach ($e->lines->where('credit', '>', 0) as $cl)
                  <div>Cr: {{ $cl->account->name }} (Rs. {{ number_format($cl->credit) }})</div>
                @endforeach
              </td>
              <td class="text-end fw-bold">Rs. {{ number_format($e->total_debit, 2) }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-center py-4 text-muted">No journal entries found.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
  @if ($entries->hasPages())
    <div class="card-footer bg-transparent py-2">
      {{ $entries->links() }}
    </div>
  @endif
</div>

<div class="modal fade" id="addVoucherModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <form method="POST" action="{{ route('finance.vouchers.store') }}">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title fw-bold">Create Double-Entry Journal Voucher</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <div class="row g-2 mb-3">
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Voucher Date</label>
              <input type="date" name="entry_date" class="form-control" value="{{ date('Y-m-d') }}" required>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Amount (Debit = Credit)</label>
              <input type="number" name="amount" class="form-control text-end fw-bold" step="0.01" min="1" required>
            </div>
          </div>
          <div class="row g-2 mb-3">
            <div class="col-md-6">
              <label class="form-label small fw-semibold text-primary">Debit Account (Dr)</label>
              <select name="debit_account_id" class="form-select" required>
                <option value="">-- Choose Account --</option>
                @foreach ($accounts as $acc)
                  <option value="{{ $acc->id }}">{{ $acc->code }} - {{ $acc->name }} ({{ ucfirst($acc->type) }})</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold text-success">Credit Account (Cr)</label>
              <select name="credit_account_id" class="form-select" required>
                <option value="">-- Choose Account --</option>
                @foreach ($accounts as $acc)
                  <option value="{{ $acc->id }}">{{ $acc->code }} - {{ $acc->name }} ({{ ucfirst($acc->type) }})</option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Narration</label>
            <input type="text" name="notes" class="form-control" placeholder="e.g. Bank deposit from counter cash" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Post Balanced Journal Voucher</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
