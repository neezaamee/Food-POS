@extends('layouts.app')

@section('title', 'Payment Methods & Collections')

@section('content')
<div class="row g-4 mb-4">
    <div class="col-12 d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <h4 class="mb-1 fw-bold text-body-emphasis">Payment Collections & Method Distribution</h4>
            <p class="text-muted mb-0 small">Cash, Credit Card, Bank Transfers, and On-Account balances breakdown</p>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
                <i class="bi bi-printer"></i> Print Report
            </button>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-3 p-3 bg-success text-white">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <div class="small opacity-75 fw-medium">Total Collected</div>
                    <div class="fs-4 fw-bold mt-1">Rs. {{ number_format($totalCollected, 2) }}</div>
                </div>
                <div class="bg-white bg-opacity-25 rounded-circle p-3 d-flex align-items-center justify-content-center">
                    <i class="bi bi-shield-check fs-4"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-3">
    <div class="card-header bg-transparent border-0 pt-3 px-3 pb-0">
        <h6 class="fw-bold mb-0">Collections by Tender Type</h6>
    </div>
    <div class="card-body p-0 mt-2">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Payment Tender / Method</th>
                        <th class="text-center">Transaction Count</th>
                        <th class="text-end">Percentage of Total</th>
                        <th class="text-end pe-3">Total Amount Collected</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($paymentStats as $p)
                    @php
                        $percentage = $totalCollected > 0 ? ($p->total_collected / $totalCollected) * 100 : 0;
                    @endphp
                    <tr>
                        <td class="ps-3 fw-bold">
                            @if($p->payment_method === 'CASH')
                                <i class="bi bi-cash-stack text-success me-2 fs-5"></i> Cash
                            @elseif($p->payment_method === 'CARD')
                                <i class="bi bi-credit-card-2-front text-primary me-2 fs-5"></i> Credit/Debit Card
                            @elseif($p->payment_method === 'BANK')
                                <i class="bi bi-bank text-info text-dark me-2 fs-5"></i> Online / Bank Transfer
                            @elseif($p->payment_method === 'CREDIT')
                                <i class="bi bi-person-lines-fill text-warning me-2 fs-5"></i> Customer Credit (Receivable)
                            @else
                                <i class="bi bi-wallet2 text-muted me-2 fs-5"></i> {{ $p->payment_method }}
                            @endif
                        </td>
                        <td class="text-center">
                            <span class="badge bg-secondary-subtle text-secondary fs-6 px-3 py-1">
                                {{ number_format($p->transaction_count) }}
                            </span>
                        </td>
                        <td class="text-end">
                            <div class="d-flex align-items-center justify-content-end gap-2">
                                <span class="small fw-medium">{{ number_format($percentage, 1) }}%</span>
                                <div class="progress flex-shrink-0" style="width: 80px; height: 6px;">
                                    <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $percentage }}%"></div>
                                </div>
                            </div>
                        </td>
                        <td class="text-end pe-3 fw-bold fs-6 text-body-emphasis">
                            Rs. {{ number_format($p->total_collected, 2) }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center py-4 text-muted">No payment transactions found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
