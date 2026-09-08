@extends('layouts.app', ['title' => 'Account Ledger - Food Point POS'])

@section('content')
<div class="page-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
  <div>
    <h1 class="page-title">General / Account Ledger</h1>
    <nav class="breadcrumb small">
      <a href="{{ route('dashboard') }}" class="breadcrumb-item text-decoration-none">Home</a>
      <a href="{{ route('finance.chart-of-accounts') }}" class="breadcrumb-item text-decoration-none">Finance</a>
      <span class="breadcrumb-item active">Account Ledger</span>
    </nav>
  </div>
</div>

<!-- Account & Date Filters -->
<div class="card border mb-4">
  <div class="card-body p-3">
    <form method="GET" action="{{ route('finance.ledger') }}" class="row g-2 align-items-center">
      <div class="col-md-5">
        <select name="account_id" class="form-select form-select-sm" required>
          @foreach ($accounts as $a)
            <option value="{{ $a->id }}" {{ ($selectedAccount?->id == $a->id) ? 'selected' : '' }}>
              {{ $a->code }} - {{ $a->name }} ({{ ucfirst($a->type) }})
            </option>
          @endforeach
        </select>
      </div>
      <div class="col-md-2">
        <input type="date" name="from_date" class="form-control form-control-sm" value="{{ request('from_date') }}" placeholder="From Date">
      </div>
      <div class="col-md-2">
        <input type="date" name="to_date" class="form-control form-control-sm" value="{{ request('to_date') }}" placeholder="To Date">
      </div>
      <div class="col-md-3 d-flex gap-2">
        <button type="submit" class="btn btn-sm btn-primary flex-grow-1">Load Ledger</button>
        <button type="button" onclick="window.print()" class="btn btn-sm btn-outline-secondary"><i class="bi bi-printer"></i></button>
      </div>
    </form>
  </div>
</div>

@if ($selectedAccount)
<div class="card border">
  <div class="card-header bg-transparent py-3 d-flex justify-content-between align-items-center">
    <div>
      <h5 class="card-title mb-0 fs-6 fw-bold">{{ $selectedAccount->code }} - {{ $selectedAccount->name }}</h5>
      <span class="badge bg-light text-dark border">{{ ucfirst($selectedAccount->type) }}</span>
      <span class="badge bg-secondary">Nature: {{ strtoupper($selectedAccount->debit_credit_nature) }}</span>
    </div>
    <div class="text-end">
      <div class="small text-muted">Closing Balance</div>
      <div class="fw-bold fs-5 text-primary">Rs. {{ number_format($selectedAccount->current_balance, 2) }}</div>
    </div>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Date</th>
            <th>Voucher #</th>
            <th>Type</th>
            <th>Description</th>
            <th class="text-end">Debit (Dr)</th>
            <th class="text-end">Credit (Cr)</th>
            <th class="text-end">Running Balance</th>
          </tr>
        </thead>
        <tbody>
          @php
            $running = 0.00;
          @endphp
          @forelse ($lines as $line)
            @php
              if ($selectedAccount->debit_credit_nature === 'debit') {
                $running += ((float)$line->debit - (float)$line->credit);
              } else {
                $running += ((float)$line->credit - (float)$line->debit);
              }
            @endphp
            <tr>
              <td class="small text-muted">{{ $line->entry->entry_date->format('d M Y') }}</td>
              <td class="fw-bold">{{ $line->entry->entry_number }}</td>
              <td><span class="badge bg-secondary">{{ strtoupper($line->entry->voucher_type) }}</span></td>
              <td class="small">{{ $line->description ?: $line->entry->notes }}</td>
              <td class="text-end fw-semibold text-primary">
                {{ $line->debit > 0 ? 'Rs. ' . number_format($line->debit, 2) : '-' }}
              </td>
              <td class="text-end fw-semibold text-success">
                {{ $line->credit > 0 ? 'Rs. ' . number_format($line->credit, 2) : '-' }}
              </td>
              <td class="text-end fw-bold text-heading">
                Rs. {{ number_format($running, 2) }}
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-center py-4 text-muted">No journal transactions posted for this account head.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endif
@endsection
