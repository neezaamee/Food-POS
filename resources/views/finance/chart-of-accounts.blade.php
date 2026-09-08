@extends('layouts.app', ['title' => 'Chart of Accounts - Food Point POS'])

@section('content')
<div class="page-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
  <div>
    <h1 class="page-title">Chart of Accounts</h1>
    <nav class="breadcrumb small">
      <a href="{{ route('dashboard') }}" class="breadcrumb-item text-decoration-none">Home</a>
      <span class="breadcrumb-item active">Chart of Accounts</span>
    </nav>
  </div>
  <div>
    <button type="button" class="btn btn-primary btn-sm px-3 py-2" data-bs-toggle="modal" data-bs-target="#addAccountModal">
      <i class="bi bi-plus-circle me-1"></i> Add Account Head
    </button>
  </div>
</div>

<div class="card border">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th style="width: 15%;">Account Code</th>
            <th style="width: 45%;">Account Head Name</th>
            <th>Type</th>
            <th>Nature</th>
            <th class="text-end">Current Balance</th>
            <th class="text-center">System</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($accounts as $main)
            <!-- Level 1: Main Head -->
            <tr class="table-secondary fw-bold">
              <td>{{ $main->code }}</td>
              <td colspan="3"><i class="ph-duotone ph-folder-notch-open me-2 text-primary"></i> {{ $main->name }}</td>
              <td class="text-end">Rs. {{ number_format($main->current_balance, 2) }}</td>
              <td class="text-center">
                <span class="badge bg-secondary">Head</span>
              </td>
            </tr>

            @foreach ($main->children as $sub)
              <!-- Level 2: Sub Head -->
              <tr class="bg-light-subtle fw-semibold">
                <td class="ps-3">{{ $sub->code }}</td>
                <td class="ps-3"><i class="ph-duotone ph-folder me-2 text-warning"></i> {{ $sub->name }}</td>
                <td><span class="badge bg-light text-dark border">{{ ucfirst($sub->type) }}</span></td>
                <td><span class="badge {{ $sub->debit_credit_nature === 'debit' ? 'bg-primary-subtle text-primary' : 'bg-success-subtle text-success' }}">{{ strtoupper($sub->debit_credit_nature) }}</span></td>
                <td class="text-end">Rs. {{ number_format($sub->current_balance, 2) }}</td>
                <td class="text-center"><span class="badge bg-light text-dark">Sub Head</span></td>
              </tr>

              @foreach ($sub->children as $detail)
                <!-- Level 3: Detail Posting Head -->
                <tr>
                  <td class="ps-5 small text-muted">{{ $detail->code }}</td>
                  <td class="ps-5 text-heading"><i class="bi bi-arrow-return-right me-2 text-muted"></i> {{ $detail->name }}</td>
                  <td class="small">{{ ucfirst($detail->type) }}</td>
                  <td><span class="badge {{ $detail->debit_credit_nature === 'debit' ? 'bg-primary' : 'bg-success' }} py-0" style="font-size: 0.7rem;">{{ strtoupper($detail->debit_credit_nature) }}</span></td>
                  <td class="text-end fw-bold text-heading">Rs. {{ number_format($detail->current_balance, 2) }}</td>
                  <td class="text-center">
                    @if ($detail->is_system)
                      <span class="badge badge-soft-primary">Protected</span>
                    @else
                      <span class="badge badge-soft-success">Custom</span>
                    @endif
                  </td>
                </tr>
              @endforeach
            @endforeach
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- ADD ACCOUNT MODAL -->
<div class="modal fade" id="addAccountModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="{{ route('finance.chart-of-accounts.store') }}">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title fw-bold">Add Account Head</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label small fw-semibold">Account Code</label>
              <input type="text" name="code" class="form-control" placeholder="e.g. 5240" required>
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold">Head Level</label>
              <select name="level" class="form-select" required>
                <option value="3" selected>Detail Posting Head (Level 3)</option>
                <option value="2">Sub Head (Level 2)</option>
              </select>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Account Name</label>
            <input type="text" name="name" class="form-control" placeholder="e.g. Electricity Expense, Packaging Material" required>
          </div>
          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label small fw-semibold">Account Type</label>
              <select name="type" class="form-select" required>
                <option value="asset">Asset</option>
                <option value="liability">Liability</option>
                <option value="equity">Equity</option>
                <option value="income">Income</option>
                <option value="expense" selected>Expense</option>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold">Debit/Credit Nature</label>
              <select name="debit_credit_nature" class="form-select" required>
                <option value="debit">Debit</option>
                <option value="credit">Credit</option>
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Account Head</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
