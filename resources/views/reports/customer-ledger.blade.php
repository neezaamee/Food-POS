@extends('layouts.app')

@section('title', 'Customer Statement & Account Ledger - Food Point POS')

@section('content')
<div class="row g-4 mb-4">
    <div class="col-12 d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <h4 class="mb-1 fw-bold text-body-emphasis"><i class="bi bi-journal-text me-2 text-primary"></i>Customer Statement & Account Ledger</h4>
            <p class="text-muted mb-0 small">Account receivables, purchase invoices, credit returns, and running remaining balances</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            @if($selectedCustomer)
            <button type="button" class="btn btn-outline-success btn-sm d-flex align-items-center gap-1 shadow-sm px-3" data-bs-toggle="modal" data-bs-target="#shareWhatsAppModal">
                <i class="bi bi-whatsapp"></i> Share via WhatsApp
                @if($whatsAppStatus['connected'] ?? false)
                    <span class="badge bg-success rounded-pill ms-1" style="font-size: 0.65rem;">Online</span>
                @else
                    <span class="badge bg-secondary rounded-pill ms-1" style="font-size: 0.65rem;">Offline</span>
                @endif
            </button>
            <button onclick="window.print()" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1 shadow-sm px-3">
                <i class="bi bi-printer"></i> Print Statement
            </button>
            @endif
        </div>
    </div>
</div>

{{-- Flash Alerts --}}
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-check-circle-fill fs-5"></i>
            <div>{{ session('success') }}</div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
        <div class="d-flex align-items-start gap-2">
            <i class="bi bi-exclamation-triangle-fill fs-5 mt-1"></i>
            <div class="flex-grow-1">
                <div class="fw-semibold">{{ session('error') }}</div>
                @if(session('whatsapp_fallback_url'))
                    <div class="mt-2 pt-2 border-top border-danger border-opacity-25">
                        <span class="small d-block mb-2">You can still share this ledger statement directly using WhatsApp Web or Desktop app:</span>
                        <a href="{{ session('whatsapp_fallback_url') }}" target="_blank" class="btn btn-sm btn-success fw-semibold">
                            <i class="bi bi-whatsapp me-1"></i> Open & Send via WhatsApp Web
                        </a>
                    </div>
                @endif
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

{{-- Customer & Date Range Selection Filter --}}
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('reports.customer-ledger') }}" class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label small text-muted mb-1 fw-semibold">Select Customer Account</label>
                <select name="customer_id" class="form-select form-select-sm" required>
                    <option value="">-- Choose a Customer --</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}" {{ (request('customer_id') ?? ($selectedCustomer->id ?? '')) == $c->id ? 'selected' : '' }}>
                            {{ $c->name }} ({{ $c->mobile ?? $c->phone ?? 'No Mobile' }}) — Bal: Rs. {{ number_format($c->current_balance ?? $c->balance, 2) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted mb-1 fw-semibold">From Date</label>
                <input type="date" name="from_date" class="form-control form-control-sm" value="{{ request('from_date') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted mb-1 fw-semibold">To Date</label>
                <input type="date" name="to_date" class="form-control form-control-sm" value="{{ request('to_date') }}">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm flex-fill d-flex align-items-center justify-content-center gap-1">
                    <i class="bi bi-filter"></i> Load Ledger
                </button>
                <a href="{{ route('reports.customer-ledger') }}" class="btn btn-light btn-sm px-3">Clear</a>
            </div>
        </form>
    </div>
</div>

@if($selectedCustomer)
{{-- Customer Snapshot & Summary KPIs --}}
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-4">
        <div class="row g-4 align-items-center">
            <div class="col-lg-5">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-3 fs-3 d-flex align-items-center justify-content-center" style="width: 58px; height: 58px;">
                        <i class="bi bi-person-badge"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <h5 class="fw-bold mb-0 text-dark">{{ $selectedCustomer->name }}</h5>
                            <span class="badge bg-secondary-subtle text-secondary small">ID: #{{ $selectedCustomer->id }}</span>
                        </div>
                        <div class="text-muted small">
                            <i class="bi bi-telephone me-1"></i>{{ $selectedCustomer->mobile ?? $selectedCustomer->phone ?? 'N/A' }} 
                            <span class="mx-1">|</span> 
                            <i class="bi bi-geo-alt me-1"></i>{{ $selectedCustomer->address ?? 'No physical address' }}
                        </div>
                        <div class="text-muted small mt-1">
                            <span>Credit Limit: <strong class="text-dark">Rs. {{ number_format($selectedCustomer->credit_limit, 2) }}</strong></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="row g-2 text-center">
                    <div class="col-sm">
                        <div class="p-2 border rounded bg-light">
                            <div class="text-muted small" style="font-size: 0.75rem;">Opening Balance</div>
                            <div class="fw-bold text-secondary">Rs. {{ number_format($summary['opening_balance'], 2) }}</div>
                        </div>
                    </div>
                    <div class="col-sm">
                        <div class="p-2 border rounded bg-light">
                            <div class="text-muted small" style="font-size: 0.75rem;">Total Invoiced</div>
                            <div class="fw-bold text-primary">Rs. {{ number_format($summary['total_invoiced'], 2) }}</div>
                        </div>
                    </div>
                    <div class="col-sm">
                        <div class="p-2 border rounded bg-light">
                            <div class="text-muted small" style="font-size: 0.75rem;">Total Paid</div>
                            <div class="fw-bold text-success">Rs. {{ number_format($summary['total_paid'], 2) }}</div>
                        </div>
                    </div>
                    <div class="col-sm">
                        <div class="p-2 border rounded bg-light">
                            <div class="text-muted small" style="font-size: 0.75rem;">Total Returns</div>
                            <div class="fw-bold text-warning">Rs. {{ number_format($summary['total_returns'], 2) }}</div>
                        </div>
                    </div>
                    <div class="col-sm">
                        <div class="p-2 border rounded {{ $summary['remaining_balance'] > 0 ? 'bg-danger bg-opacity-10 border-danger border-opacity-25' : 'bg-success bg-opacity-10 border-success border-opacity-25' }}">
                            <div class="small fw-semibold {{ $summary['remaining_balance'] > 0 ? 'text-danger' : 'text-success' }}" style="font-size: 0.75rem;">Remaining Balance</div>
                            <div class="fw-bold {{ $summary['remaining_balance'] > 0 ? 'text-danger' : 'text-success' }}" style="font-size: 1.05rem;">
                                Rs. {{ number_format($summary['remaining_balance'], 2) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Unified Chronological Transaction Ledger Table --}}
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-header bg-transparent border-0 pt-3 px-3 pb-2 d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-clock-history me-1 text-primary"></i> Transaction & Balance Statement</h6>
        <span class="badge bg-light text-muted border">
            Showing {{ $ledgerEntries->count() }} transaction(s)
            @if(request('from_date') || request('to_date'))
                ({{ request('from_date', 'Start') }} to {{ request('to_date', 'Present') }})
            @endif
        </span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3" style="min-width: 140px;">Date & Time</th>
                        <th>Voucher / Ref #</th>
                        <th>Type</th>
                        <th>Description</th>
                        <th class="text-end">Debit (Bill)</th>
                        <th class="text-end">Credit (Paid)</th>
                        <th class="text-end bg-light-subtle pe-3" style="min-width: 150px;">Remaining Balance</th>
                        <th class="text-center pe-3">Status</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- Opening Balance Baseline Row --}}
                    <tr class="table-secondary bg-opacity-25">
                        <td class="ps-3 text-muted small">
                            <i class="bi bi-calendar3 me-1"></i>
                            {{ request('from_date') ? \Carbon\Carbon::parse(request('from_date'))->format('M d, Y') : ($selectedCustomer->created_at ? $selectedCustomer->created_at->format('M d, Y') : 'Start') }}
                        </td>
                        <td><span class="badge bg-secondary-subtle text-secondary font-monospace">OB-START</span></td>
                        <td><span class="badge bg-secondary-subtle text-secondary">OPENING BALANCE</span></td>
                        <td class="text-muted fst-italic">Initial Opening Balance Brought Forward</td>
                        <td class="text-end text-muted">-</td>
                        <td class="text-end text-muted">-</td>
                        <td class="text-end fw-bold bg-light-subtle pe-3 {{ $summary['opening_balance'] > 0 ? 'text-danger' : 'text-body' }}">
                            Rs. {{ number_format($summary['opening_balance'], 2) }}
                        </td>
                        <td class="text-center pe-3"><span class="badge bg-secondary-subtle text-secondary">Baseline</span></td>
                    </tr>

                    {{-- Transaction Entries --}}
                    @forelse($ledgerEntries as $entry)
                    <tr>
                        <td class="ps-3 text-muted small">
                            {{ \Carbon\Carbon::parse($entry->date)->format('M d, Y h:i A') }}
                        </td>
                        <td>
                            @if($entry->type === 'invoice' && $entry->order_id)
                                <a href="{{ route('orders.show', $entry->order_id) }}" class="fw-bold text-decoration-none font-monospace">
                                    #{{ $entry->reference }}
                                </a>
                            @else
                                <span class="fw-bold text-secondary font-monospace">#{{ $entry->reference }}</span>
                            @endif
                        </td>
                        <td>
                            @if($entry->type === 'invoice')
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">{{ $entry->type_label }}</span>
                            @else
                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">{{ $entry->type_label }}</span>
                            @endif
                        </td>
                        <td class="text-muted small">
                            {{ $entry->description }}
                        </td>
                        <td class="text-end fw-semibold {{ $entry->debit > 0 ? 'text-primary' : 'text-muted' }}">
                            {{ $entry->debit > 0 ? 'Rs. ' . number_format($entry->debit, 2) : '-' }}
                        </td>
                        <td class="text-end fw-semibold {{ $entry->credit > 0 ? 'text-success' : 'text-muted' }}">
                            {{ $entry->credit > 0 ? 'Rs. ' . number_format($entry->credit, 2) : '-' }}
                        </td>
                        <td class="text-end fw-bold bg-light-subtle pe-3 {{ $entry->remaining_balance > 0 ? 'text-danger' : 'text-success' }}">
                            Rs. {{ number_format($entry->remaining_balance, 2) }}
                        </td>
                        <td class="text-center pe-3">
                            @if($entry->payment_status === 'paid')
                                <span class="badge bg-success-subtle text-success border border-success-subtle">Paid</span>
                            @elseif($entry->payment_status === 'partial')
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Partial</span>
                            @elseif($entry->payment_status === 'refunded')
                                <span class="badge bg-info-subtle text-info border border-info-subtle">Refunded</span>
                            @else
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Due</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">
                            No additional transactions found for this customer in the selected period.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                <tfoot class="table-light">
                    <tr class="fw-bold">
                        <td colspan="4" class="text-end ps-3">Period Ledger Totals:</td>
                        <td class="text-end text-primary">Rs. {{ number_format($summary['total_invoiced'], 2) }}</td>
                        <td class="text-end text-success">Rs. {{ number_format($summary['total_paid'], 2) }}</td>
                        <td class="text-end pe-3 fs-6 {{ $summary['remaining_balance'] > 0 ? 'text-danger' : 'text-success' }}">
                            Rs. {{ number_format($summary['remaining_balance'], 2) }}
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

{{-- WhatsApp Share Modal --}}
<div class="modal fade" id="shareWhatsAppModal" tabindex="-1" aria-labelledby="shareWhatsAppModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('reports.customer-ledger.share-whatsapp') }}" method="POST">
                @csrf
                <input type="hidden" name="customer_id" value="{{ $selectedCustomer->id }}">
                <div class="modal-header py-2 bg-success text-white">
                    <h6 class="modal-title fw-bold mb-0 d-flex align-items-center gap-2" id="shareWhatsAppModalLabel">
                        <i class="bi bi-whatsapp"></i> Share Customer Balance via WhatsApp
                    </h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    {{-- Connection Status Banner --}}
                    @if($whatsAppStatus['connected'] ?? false)
                        <div class="alert alert-success d-flex align-items-center gap-2 py-2 small mb-3 border-0">
                            <i class="bi bi-check-circle-fill fs-5"></i>
                            <div><strong>WhatsApp Connected:</strong> Balance statement will be delivered directly from your paired WhatsApp session.</div>
                        </div>
                    @else
                        <div class="alert alert-warning d-flex align-items-start gap-2 py-2 small mb-3 border-0">
                            <i class="bi bi-exclamation-triangle-fill fs-5 text-warning mt-1"></i>
                            <div>
                                <strong>WhatsApp Disconnected:</strong> System validates connection first. If disconnected, a direct WhatsApp Web link is provided to dispatch instantly.
                            </div>
                        </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Customer WhatsApp Mobile Number</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="bi bi-telephone"></i></span>
                            <input type="text" name="phone" class="form-control" value="{{ old('phone', $selectedCustomer->mobile ?? $selectedCustomer->phone) }}" placeholder="e.g. 03001234567 or 923001234567" required>
                        </div>
                        <div class="form-text small text-muted">Local or international format accepted.</div>
                    </div>

                    {{-- Statement Preview Card --}}
                    <label class="form-label small fw-semibold mb-1">Message Preview</label>
                    <div class="p-3 bg-light rounded border small font-monospace text-muted mb-3" style="max-height: 180px; overflow-y: auto; white-space: pre-line;">
*CUSTOMER ACCOUNT STATEMENT*
Customer: {{ $selectedCustomer->name }}
Date: {{ now()->format('d M Y, h:i A') }}
---------------------------------
• Opening Balance: Rs. {{ number_format($summary['opening_balance'], 2) }}
• Total Invoiced:  Rs. {{ number_format($summary['total_invoiced'], 2) }}
• Total Payments:  Rs. {{ number_format($summary['total_paid'], 2) }}
• Total Returns:   Rs. {{ number_format($summary['total_returns'], 2) }}
---------------------------------
*NET REMAINING BALANCE:*
*Rs. {{ number_format($summary['remaining_balance'], 2) }}*
---------------------------------
Thank you for your business!
                    </div>
                </div>
                <div class="modal-footer bg-light py-2 px-4 border-top">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success btn-sm px-3 fw-semibold d-flex align-items-center gap-1">
                        <i class="bi bi-whatsapp"></i> Send Statement
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@else
<div class="card border-0 shadow-sm rounded-3 py-5 text-center text-muted">
    <i class="bi bi-journal-text fs-1 mb-2 text-primary opacity-50"></i>
    <h6>Please select a customer above to display their financial statement & balance ledger</h6>
</div>
@endif
@endsection
