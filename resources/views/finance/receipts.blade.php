@extends('layouts.app', ['title' => 'Cash Receipts - Food Point POS'])

@section('content')
<div class="page-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
  <div>
    <h1 class="page-title">Cash Receipt Vouchers (CRV)</h1>
    <nav class="breadcrumb small">
      <a href="{{ route('dashboard') }}" class="breadcrumb-item text-decoration-none">Home</a>
      <a href="{{ route('finance.chart-of-accounts') }}" class="breadcrumb-item text-decoration-none">Finance</a>
      <span class="breadcrumb-item active">Cash Receipts</span>
    </nav>
  </div>
  <div>
    <button type="button" class="btn btn-primary btn-sm px-3 py-2" data-bs-toggle="modal" data-bs-target="#addReceiptModal">
      <i class="bi bi-plus-circle me-1"></i> New Cash Receipt
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
            <th>Description / Source</th>
            <th>Debit Account</th>
            <th>Credit Account</th>
            <th class="text-end">Amount</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($entries as $e)
            <tr>
              <td class="fw-bold text-success">{{ $e->entry_number }}</td>
              <td class="small text-muted">{{ $e->entry_date->format('d M Y') }}</td>
              <td>{{ $e->notes }}</td>
              <td>
                <span class="badge bg-light text-dark border">
                  {{ $e->lines->where('debit', '>', 0)->first()?->account?->name ?? 'Cash on Hand' }}
                </span>
              </td>
              <td>
                <span class="badge bg-light text-dark border">
                  {{ $e->lines->where('credit', '>', 0)->first()?->account?->name ?? 'Source' }}
                </span>
              </td>
              <td class="text-end fw-bold text-success">Rs. {{ number_format($e->total_debit, 2) }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="text-center py-4 text-muted">No cash receipts recorded.</td>
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

<div class="modal fade" id="addReceiptModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="{{ route('finance.receipts.store') }}">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title fw-bold">Receive Cash Voucher</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Receipt Date</label>
            <input type="date" name="entry_date" class="form-control" value="{{ date('Y-m-d') }}" required>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Credit Source Account</label>
            <select name="from_account_id" class="form-select" required>
              <option value="">-- Choose Account --</option>
              @foreach ($accounts as $acc)
                <option value="{{ $acc->id }}">{{ $acc->code }} - {{ $acc->name }} ({{ ucfirst($acc->type) }})</option>
              @endforeach
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Amount (Rs.)</label>
            <input type="number" name="amount" class="form-control form-control-lg text-end fw-bold" step="0.01" min="1" required>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Narration / Description</label>
            <input type="text" name="description" class="form-control" placeholder="e.g. Customer recovery, Advance received" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Post Cash Receipt</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
