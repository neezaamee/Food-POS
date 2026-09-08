@extends('layouts.app', ['title' => 'Trial Balance - Food Point POS'])

@section('content')
<div class="page-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
  <div>
    <h1 class="page-title">Trial Balance</h1>
    <nav class="breadcrumb small">
      <a href="{{ route('dashboard') }}" class="breadcrumb-item text-decoration-none">Home</a>
      <a href="{{ route('finance.chart-of-accounts') }}" class="breadcrumb-item text-decoration-none">Finance</a>
      <span class="breadcrumb-item active">Trial Balance</span>
    </nav>
  </div>
  <div>
    <button onclick="window.print()" class="btn btn-primary btn-sm px-3 py-2">
      <i class="bi bi-printer me-1"></i> Print Trial Balance
    </button>
  </div>
</div>

<!-- Balance Status Banner -->
@php
  $diff = abs($totalDebit - $totalCredit);
@endphp
<div class="alert {{ $diff < 0.01 ? 'alert-success' : 'alert-danger' }} d-flex align-items-center justify-content-between mb-4">
  <div class="d-flex align-items-center gap-2">
    <i class="bi {{ $diff < 0.01 ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' }} fs-5"></i>
    <div>
      <strong>{{ $diff < 0.01 ? 'Trial Balance is in Equilibrium!' : 'Trial Balance Discrepancy Detected!' }}</strong>
      <div class="small">Total Debit: Rs. {{ number_format($totalDebit, 2) }} | Total Credit: Rs. {{ number_format($totalCredit, 2) }}</div>
    </div>
  </div>
  <span class="badge {{ $diff < 0.01 ? 'bg-success' : 'bg-danger' }} fs-6">
    {{ $diff < 0.01 ? 'Balanced (Diff: Rs. 0.00)' : 'Out of Balance by Rs. ' . number_format($diff, 2) }}
  </span>
</div>

<div class="card border">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th style="width: 15%;">Account Code</th>
            <th style="width: 45%;">Account Head Title</th>
            <th style="width: 15%;">Account Type</th>
            <th class="text-end" style="width: 12.5%;">Debit (Dr)</th>
            <th class="text-end" style="width: 12.5%;">Credit (Cr)</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($accounts as $acc)
            <tr>
              <td class="font-monospace fw-bold">{{ $acc->code }}</td>
              <td class="text-heading fw-semibold">{{ $acc->name }}</td>
              <td><span class="badge bg-light text-dark border">{{ ucfirst($acc->type) }}</span></td>
              <td class="text-end fw-semibold text-primary">
                {{ $acc->calc_debit > 0 ? 'Rs. ' . number_format($acc->calc_debit, 2) : '-' }}
              </td>
              <td class="text-end fw-semibold text-success">
                {{ $acc->calc_credit > 0 ? 'Rs. ' . number_format($acc->calc_credit, 2) : '-' }}
              </td>
            </tr>
          @endforeach
        </tbody>
        <tfoot class="table-dark fw-bold">
          <tr>
            <td colspan="3" class="text-uppercase text-end fs-6">Total Trial Balance:</td>
            <td class="text-end fs-6 text-white">Rs. {{ number_format($totalDebit, 2) }}</td>
            <td class="text-end fs-6 text-white">Rs. {{ number_format($totalCredit, 2) }}</td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
</div>
@endsection
