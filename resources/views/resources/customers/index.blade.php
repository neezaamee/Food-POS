@extends('layouts.app', ['title' => 'Customers - Food Point POS'])

@section('content')
<div class="page-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
  <div>
    <h1 class="page-title">Customers & Ledger Profiles</h1>
    <nav class="breadcrumb small">
      <a href="{{ route('dashboard') }}" class="breadcrumb-item text-decoration-none">Home</a>
      <span class="breadcrumb-item active">Customers</span>
    </nav>
  </div>
  <div>
    <button type="button" class="btn btn-primary btn-sm px-3 py-2" data-bs-toggle="modal" data-bs-target="#addCustomerModal">
      <i class="bi bi-person-plus me-1"></i> Add Customer
    </button>
  </div>
</div>

<div class="card border mb-4">
  <div class="card-body p-3">
    <form method="GET" action="{{ route('resources.customers.index') }}" class="row g-2 align-items-center">
      <div class="col-md-9">
        <div class="input-group input-group-sm">
          <span class="input-group-text bg-surface"><i class="bi bi-search"></i></span>
          <input type="text" name="search" class="form-control" placeholder="Search customer name, mobile, area..." value="{{ request('search') }}">
        </div>
      </div>
      <div class="col-md-3 d-flex gap-2">
        <button type="submit" class="btn btn-sm btn-primary flex-grow-1">Search</button>
        <a href="{{ route('resources.customers.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
      </div>
    </form>
  </div>
</div>

<div class="card border">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Customer Name</th>
            <th>Mobile</th>
            <th>Area / Address</th>
            <th>Credit Limit</th>
            <th>Receivable Balance</th>
            <th>Total Orders</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($customers as $cust)
            <tr>
              <td class="fw-bold text-heading">{{ $cust->name }}</td>
              <td>{{ $cust->mobile }}</td>
              <td class="small text-muted">{{ $cust->area ?: ($cust->address ?: '-') }}</td>
              <td>Rs. {{ number_format($cust->credit_limit, 2) }}</td>
              <td class="fw-bold {{ $cust->current_balance > 0 ? 'text-danger' : 'text-success' }}">
                Rs. {{ number_format($cust->current_balance, 2) }}
              </td>
              <td>{{ $cust->orders_count }} orders</td>
              <td>
                <span class="badge {{ $cust->is_active ? 'badge-soft-success' : 'badge-soft-danger' }}">
                  {{ $cust->is_active ? 'Active' : 'Inactive' }}
                </span>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-center py-4 text-muted">No customers found.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
  @if ($customers->hasPages())
    <div class="card-footer bg-transparent py-2">
      {{ $customers->links() }}
    </div>
  @endif
</div>

<div class="modal fade" id="addCustomerModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="{{ route('resources.customers.store') }}">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title fw-bold">Register Customer</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Customer Full Name</label>
            <input type="text" name="name" class="form-control" placeholder="e.g. Tariq Mahmood" required>
          </div>
          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label small fw-semibold">Mobile Number</label>
              <input type="text" name="mobile" class="form-control" placeholder="0300-1234567" required>
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold">Credit Limit (Rs.)</label>
              <input type="number" name="credit_limit" class="form-control" value="0.00" step="1000" min="0">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Area / Sector</label>
            <input type="text" name="area" class="form-control" placeholder="e.g. Madina Town">
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Delivery Address</label>
            <textarea name="address" class="form-control" rows="2" placeholder="House #, Street #"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Customer</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
