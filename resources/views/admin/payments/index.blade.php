@extends('layouts.app')

@section('title', 'Third-Party Payment Providers & Digital Wallets - Food Point POS')

@section('content')
<div class="row g-4 mb-4">
    <div class="col-12 d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <h4 class="mb-1 fw-bold text-body-emphasis"><i class="ph-duotone ph-credit-card me-2 text-primary"></i>Third-Party Payment Gateways & Digital Wallets</h4>
            <p class="text-muted mb-0 small">Configure JazzCash, EasyPaisa, NayaPay, Raast Interoperable QR, and the Testing Sandbox Simulator</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1 shadow-sm px-3" data-bs-toggle="modal" data-bs-target="#diagnosticTestModal">
                <i class="bi bi-lightning-charge"></i> Test Payment Diagnostic
            </button>
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
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
    <div class="d-flex align-items-center gap-2">
        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
        <div>{{ session('error') }}</div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Provider Status Cards --}}
<div class="row g-3 mb-4">
    @foreach($providers as $slug => $p)
    <div class="col-md-6 col-xl-2 col-sm-6 flex-fill">
        <div class="card border-0 shadow-sm rounded-3 h-100 p-3">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <span class="fw-bold small">{{ $p['display_name'] }}</span>
                @if($p['enabled'])
                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill" style="font-size: 0.65rem;">Active</span>
                @else
                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill" style="font-size: 0.65rem;">Disabled</span>
                @endif
            </div>
            <div class="d-flex align-items-center justify-content-between mt-auto">
                <span class="badge {{ $p['is_sandbox'] ? 'bg-warning-subtle text-warning border border-warning-subtle' : 'bg-primary-subtle text-primary border border-primary-subtle' }}" style="font-size: 0.7rem;">
                    {{ strtoupper($p['environment']) }}
                </span>
                <span class="text-muted small" style="font-size: 0.75rem;">
                    {{ $p['is_configured'] ? 'Ready' : 'Setup needed' }}
                </span>
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- Main Configuration Tabs & Forms --}}
<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
                <ul class="nav nav-pills card-header-pills gap-1" id="paymentTabs" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active fw-semibold small py-2 px-3" id="jazzcash-tab" data-bs-toggle="tab" data-bs-target="#jazzcash-pane" type="button">
                            🟠 JazzCash
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link fw-semibold small py-2 px-3" id="easypaisa-tab" data-bs-toggle="tab" data-bs-target="#easypaisa-pane" type="button">
                            🟢 EasyPaisa
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link fw-semibold small py-2 px-3" id="nayapay-tab" data-bs-toggle="tab" data-bs-target="#nayapay-pane" type="button">
                            🔵 NayaPay / SadaPay
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link fw-semibold small py-2 px-3" id="raast-tab" data-bs-toggle="tab" data-bs-target="#raast-pane" type="button">
                            🟣 Raast Dynamic QR
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link fw-semibold small py-2 px-3" id="simulator-tab" data-bs-toggle="tab" data-bs-target="#simulator-pane" type="button">
                            🧪 Testing Simulator
                        </button>
                    </li>
                </ul>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('admin.payments.settings') }}">
                    @csrf
                    
                    <div class="tab-content" id="paymentTabContent">
                        {{-- 1. JazzCash Tab --}}
                        <div class="tab-pane fade show active" id="jazzcash-pane" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                                <div>
                                    <h6 class="fw-bold mb-0 text-dark">JazzCash MWALLET & Checkout Integration</h6>
                                    <span class="text-muted small">Supports direct mobile number push prompt and QR scan</span>
                                </div>
                                <div class="form-check form-switch fs-5">
                                    <input class="form-check-input" type="checkbox" name="jazzcash_enabled" value="1" id="jazzcashEnabled" {{ $settings['jazzcash_enabled'] ? 'checked' : '' }}>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Environment Mode</label>
                                    <select name="jazzcash_environment" class="form-select form-select-sm">
                                        <option value="sandbox" {{ $settings['jazzcash_environment'] === 'sandbox' ? 'selected' : '' }}>Sandbox / Testing</option>
                                        <option value="production" {{ $settings['jazzcash_environment'] === 'production' ? 'selected' : '' }}>Production / Live</option>
                                    </select>
                                    <div class="form-text small">In Sandbox mode, simulated approvals operate if live credentials are not set.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Merchant ID</label>
                                    <input type="text" name="jazzcash_merchant_id" class="form-control form-control-sm" value="{{ $settings['jazzcash_merchant_id'] }}" placeholder="e.g. MC12345 or JC-TEST-MERCHANT">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Password</label>
                                    <input type="password" name="jazzcash_password" class="form-control form-control-sm" value="{{ $settings['jazzcash_password'] }}" placeholder="JazzCash API Password">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Integrity Salt (Secret Hash Key)</label>
                                    <input type="password" name="jazzcash_integrity_salt" class="form-control form-control-sm" value="{{ $settings['jazzcash_integrity_salt'] }}" placeholder="HMAC-SHA256 Integrity Salt">
                                </div>
                            </div>
                        </div>

                        {{-- 2. EasyPaisa Tab --}}
                        <div class="tab-pane fade" id="easypaisa-pane" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                                <div>
                                    <h6 class="fw-bold mb-0 text-dark">EasyPaisa Mobile Account (MA) Integration</h6>
                                    <span class="text-muted small">Supports direct in-app push notifications and QR payment</span>
                                </div>
                                <div class="form-check form-switch fs-5">
                                    <input class="form-check-input" type="checkbox" name="easypaisa_enabled" value="1" id="easypaisaEnabled" {{ $settings['easypaisa_enabled'] ? 'checked' : '' }}>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Environment Mode</label>
                                    <select name="easypaisa_environment" class="form-select form-select-sm">
                                        <option value="sandbox" {{ $settings['easypaisa_environment'] === 'sandbox' ? 'selected' : '' }}>Sandbox / Testing</option>
                                        <option value="production" {{ $settings['easypaisa_environment'] === 'production' ? 'selected' : '' }}>Production / Live</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Store ID</label>
                                    <input type="text" name="easypaisa_store_id" class="form-control form-control-sm" value="{{ $settings['easypaisa_store_id'] }}" placeholder="e.g. 10293">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Hash Key</label>
                                    <input type="password" name="easypaisa_hash_key" class="form-control form-control-sm" value="{{ $settings['easypaisa_hash_key'] }}" placeholder="EasyPaisa Hash Key">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Merchant Mobile / Account Number</label>
                                    <input type="text" name="easypaisa_account_number" class="form-control form-control-sm" value="{{ $settings['easypaisa_account_number'] }}" placeholder="e.g. 03451234567">
                                </div>
                            </div>
                        </div>

                        {{-- 3. NayaPay Tab --}}
                        <div class="tab-pane fade" id="nayapay-pane" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                                <div>
                                    <h6 class="fw-bold mb-0 text-dark">NayaPay / SadaPay Merchant API</h6>
                                    <span class="text-muted small">Generates dynamic merchant checkout tokens & QR codes</span>
                                </div>
                                <div class="form-check form-switch fs-5">
                                    <input class="form-check-input" type="checkbox" name="nayapay_enabled" value="1" id="nayapayEnabled" {{ $settings['nayapay_enabled'] ? 'checked' : '' }}>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Environment Mode</label>
                                    <select name="nayapay_environment" class="form-select form-select-sm">
                                        <option value="sandbox" {{ $settings['nayapay_environment'] === 'sandbox' ? 'selected' : '' }}>Sandbox / Testing</option>
                                        <option value="production" {{ $settings['nayapay_environment'] === 'production' ? 'selected' : '' }}>Production / Live</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Client ID</label>
                                    <input type="text" name="nayapay_client_id" class="form-control form-control-sm" value="{{ $settings['nayapay_client_id'] }}" placeholder="NayaPay Client ID">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Client Secret</label>
                                    <input type="password" name="nayapay_client_secret" class="form-control form-control-sm" value="{{ $settings['nayapay_client_secret'] }}" placeholder="NayaPay Client Secret">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Terminal ID</label>
                                    <input type="text" name="nayapay_terminal_id" class="form-control form-control-sm" value="{{ $settings['nayapay_terminal_id'] }}" placeholder="e.g. TID-01">
                                </div>
                            </div>
                        </div>

                        {{-- 4. Raast Dynamic QR Tab --}}
                        <div class="tab-pane fade" id="raast-pane" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                                <div>
                                    <h6 class="fw-bold mb-0 text-dark">Raast P2M Interoperable Dynamic QR</h6>
                                    <span class="text-muted small">Standard EMVCo QR code readable by ALL Pakistani banking apps (HBL, Meezan, Alfalah, etc.)</span>
                                </div>
                                <div class="form-check form-switch fs-5">
                                    <input class="form-check-input" type="checkbox" name="raast_enabled" value="1" id="raastEnabled" {{ $settings['raast_enabled'] ? 'checked' : '' }}>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Environment Mode</label>
                                    <select name="raast_environment" class="form-select form-select-sm">
                                        <option value="sandbox" {{ $settings['raast_environment'] === 'sandbox' ? 'selected' : '' }}>Sandbox / Testing</option>
                                        <option value="production" {{ $settings['raast_environment'] === 'production' ? 'selected' : '' }}>Production / Live</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Business IBAN / Raast ID</label>
                                    <input type="text" name="raast_iban" class="form-control form-control-sm font-monospace" value="{{ $settings['raast_iban'] }}" placeholder="PK00FOOD0000001234567890">
                                    <div class="form-text small">Your business bank account IBAN registered with State Bank Raast P2M.</div>
                                </div>
                            </div>
                        </div>

                        {{-- 5. Testing Simulator Tab --}}
                        <div class="tab-pane fade" id="simulator-pane" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                                <div>
                                    <h6 class="fw-bold mb-0 text-dark">Interactive Testing Sandbox Simulator</h6>
                                    <span class="text-muted small">Provides zero-barrier instant testing in POS live checkout</span>
                                </div>
                                <div class="form-check form-switch fs-5">
                                    <input class="form-check-input" type="checkbox" name="simulator_enabled" value="1" id="simulatorEnabled" {{ $settings['simulator_enabled'] ? 'checked' : '' }}>
                                </div>
                            </div>

                            <div class="alert alert-info border-0 shadow-sm small mb-3">
                                <div class="d-flex gap-2 align-items-start">
                                    <i class="bi bi-info-circle-fill fs-5 mt-1"></i>
                                    <div>
                                        <strong>How Testing Mode Works:</strong> When enabled, you do not need live merchant accounts or live money to test checkout. In POS Live, choosing <strong>Digital Wallet</strong> will display interactive simulator buttons:
                                        <ul class="mb-0 mt-2 ps-3">
                                            <li><strong class="text-success">[Simulate Customer Approved]</strong>: Instantly simulates customer entering MPIN and generates a valid mock Transaction ID.</li>
                                            <li><strong class="text-danger">[Simulate Rejected]</strong>: Simulates insufficient funds or customer cancellation.</li>
                                            <li><strong class="text-secondary">[Simulate Timeout]</strong>: Simulates USSD session expiry after 60s.</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <label class="small fw-semibold text-muted">Default Digital Provider:</label>
                            <select name="default_digital_payment_provider" class="form-select form-select-sm" style="width: 170px;">
                                <option value="jazzcash" {{ $settings['default_provider'] === 'jazzcash' ? 'selected' : '' }}>JazzCash</option>
                                <option value="easypaisa" {{ $settings['default_provider'] === 'easypaisa' ? 'selected' : '' }}>EasyPaisa</option>
                                <option value="nayapay" {{ $settings['default_provider'] === 'nayapay' ? 'selected' : '' }}>NayaPay</option>
                                <option value="raast" {{ $settings['default_provider'] === 'raast' ? 'selected' : '' }}>Raast QR</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold">
                            <i class="bi bi-save me-1"></i> Save Configuration
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Quick Test & Sandbox Instructions Card --}}
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
                <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-shield-check text-success me-1"></i> Sandbox Testing Quick Guide</h6>
            </div>
            <div class="card-body p-4 small text-muted">
                <p>You can test payments in <strong>two easy ways</strong>:</p>
                
                <div class="mb-3 p-3 bg-light rounded border">
                    <strong class="text-dark d-block mb-1">1. Direct POS Live Checkout</strong>
                    <span>Go to POS screen, add food items to cart, click Cash Out, choose <strong>Wallet / QR</strong>, and click <em>[Simulate Approved]</em>.</span>
                </div>

                <div class="mb-3 p-3 bg-light rounded border">
                    <strong class="text-dark d-block mb-1">2. Diagnostic Test API Tool</strong>
                    <span>Click the <strong>Test Payment Diagnostic</strong> button at the top to simulate an instant live push request or QR generation.</span>
                </div>

                <div class="border-top pt-3">
                    <span class="d-block text-dark fw-semibold mb-1">Live Deployment:</span>
                    <span>When ready to collect real money, contact JazzCash or EasyPaisa business sales for your production credentials, enter them in the tabs on the left, and toggle environment to <strong>Production</strong>.</span>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Digital Payment Transactions Audit Trail --}}
<div class="card border-0 shadow-sm rounded-3">
    <div class="card-header bg-transparent border-0 pt-4 px-4 pb-2 d-flex justify-content-between align-items-center">
        <div>
            <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-journal-check text-primary me-1"></i> Digital Gateway Transaction Audit Logs</h6>
            <span class="text-muted small">Real-time log of all customer mobile prompts, QR codes, and authorizations</span>
        </div>
        <span class="badge bg-light text-muted border">Total: {{ $recentTransactions->total() }} transaction(s)</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small text-muted">
                    <tr>
                        <th class="ps-4">Date & Time</th>
                        <th>Provider</th>
                        <th>Channel</th>
                        <th>Transaction Ref (TID)</th>
                        <th>Customer Mobile</th>
                        <th>Order #</th>
                        <th class="text-end">Amount</th>
                        <th class="text-center">Status</th>
                        <th class="text-end pe-4">Action</th>
                    </tr>
                </thead>
                <tbody class="small">
                    @forelse($recentTransactions as $tx)
                    <tr>
                        <td class="ps-4 text-muted">{{ $tx->created_at->format('M d, Y h:i A') }}</td>
                        <td>
                            @if($tx->provider === 'jazzcash')
                                <span class="badge bg-warning bg-opacity-10 text-warning-emphasis border border-warning-subtle">🟠 JazzCash</span>
                            @elseif($tx->provider === 'easypaisa')
                                <span class="badge bg-success-subtle text-success border border-success-subtle">🟢 EasyPaisa</span>
                            @elseif($tx->provider === 'nayapay')
                                <span class="badge bg-info-subtle text-info border border-info-subtle">🔵 NayaPay</span>
                            @else
                                <span class="badge bg-purple bg-opacity-10 text-primary border border-primary-subtle">🟣 Raast</span>
                            @endif
                        </td>
                        <td><span class="badge bg-light text-muted border">{{ strtoupper(str_replace('_', ' ', $tx->channel)) }}</span></td>
                        <td class="font-monospace fw-semibold">{{ $tx->transaction_reference }}</td>
                        <td>{{ $tx->mobile_number ?: 'N/A' }}</td>
                        <td>
                            @if($tx->order)
                                <a href="{{ route('orders.show', $tx->order->id) }}" class="fw-bold text-decoration-none">#{{ $tx->order_number }}</a>
                            @else
                                <span class="text-muted">#{{ $tx->order_number }}</span>
                            @endif
                        </td>
                        <td class="text-end fw-bold">Rs. {{ number_format($tx->amount, 2) }}</td>
                        <td class="text-center">
                            @if($tx->status === 'completed')
                                <span class="badge bg-success-subtle text-success border border-success-subtle">Approved</span>
                            @elseif($tx->status === 'pending_customer')
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Pending MPIN</span>
                            @elseif($tx->status === 'failed')
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Failed</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">{{ ucfirst($tx->status) }}</span>
                            @endif
                        </td>
                        <td class="text-end pe-4">
                            @if($tx->status === 'pending_customer')
                                <button type="button" class="btn btn-xs btn-outline-success py-1 px-2 simulate-approve-btn" data-ref="{{ $tx->transaction_reference }}">
                                    <i class="bi bi-check2"></i> Approve
                                </button>
                            @else
                                <span class="text-muted small">{{ $tx->paid_at ? $tx->paid_at->format('h:i A') : '-' }}</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted">
                            No digital gateway transactions recorded yet. Run a test transaction or process an order via POS Live.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($recentTransactions->hasPages())
        <div class="p-3 border-top">
            {{ $recentTransactions->links() }}
        </div>
        @endif
    </div>
</div>

{{-- Diagnostic Test Modal --}}
<div class="modal fade" id="diagnosticTestModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 460px;">
        <div class="modal-content border-0 shadow">
            <div class="modal-header py-2 bg-primary text-white">
                <h6 class="modal-title fw-bold mb-0 d-flex align-items-center gap-2">
                    <i class="bi bi-lightning-charge"></i> Diagnostic Payment Test
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-muted small mb-3">Simulate an instant gateway request to test connectivity and validation.</p>
                <form id="diagnosticTestForm">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Select Provider</label>
                        <select name="provider" class="form-select form-select-sm" id="testProvider">
                            <option value="jazzcash">JazzCash</option>
                            <option value="easypaisa">EasyPaisa</option>
                            <option value="nayapay">NayaPay</option>
                            <option value="raast">Raast Dynamic QR</option>
                            <option value="simulator">Sandbox Simulator</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Payment Channel</label>
                        <select name="channel" class="form-select form-select-sm" id="testChannel">
                            <option value="push_request">Mobile Push Request (USSD / In-App)</option>
                            <option value="dynamic_qr">Dynamic QR Code</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Customer Mobile Number</label>
                        <input type="text" name="mobile" class="form-control form-control-sm" value="03001234567" required id="testMobile">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Test Amount (Rs.)</label>
                        <input type="number" name="amount" class="form-control form-control-sm" value="10.00" step="0.01" required id="testAmount">
                    </div>

                    <div id="diagnosticResult" class="d-none alert small border-0 mt-3 mb-0"></div>

                    <div class="mt-4 text-end">
                        <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm px-3 fw-semibold" id="btnRunTest">
                            <i class="bi bi-send me-1"></i> Send Test Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Diagnostic test AJAX
    const testForm = document.getElementById('diagnosticTestForm');
    if (testForm) {
        testForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = document.getElementById('btnRunTest');
            const resBox = document.getElementById('diagnosticResult');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Testing...';
            resBox.className = 'd-none alert small border-0 mt-3 mb-0';

            try {
                const formData = new FormData(testForm);
                const resp = await fetch("{{ route('admin.payments.test') }}", {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await resp.json();

                resBox.classList.remove('d-none');
                if (data.success) {
                    resBox.classList.add('alert-success');
                    resBox.innerHTML = '<strong>Success:</strong> ' + (data.result.message || 'Request executed successfully!') + '<br><small class="font-monospace">TID: ' + (data.result.transaction_id || '') + '</small>';
                } else {
                    resBox.classList.add('alert-danger');
                    resBox.innerHTML = '<strong>Error:</strong> ' + (data.result.message || 'Failed to dispatch request');
                }
            } catch (err) {
                resBox.classList.remove('d-none');
                resBox.classList.add('alert-danger');
                resBox.innerText = 'Request failed: ' + err.message;
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-send me-1"></i> Send Test Request';
            }
        });
    }

    // Quick simulate approve button
    document.querySelectorAll('.simulate-approve-btn').forEach(btn => {
        btn.addEventListener('click', async function() {
            const ref = this.getAttribute('data-ref');
            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

            try {
                const formData = new FormData();
                formData.append('_token', '{{ csrf_token() }}');
                formData.append('transaction_reference', ref);
                formData.append('action', 'approve');

                const resp = await fetch("{{ route('admin.payments.simulate') }}", {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await resp.json();
                if (data.success) {
                    window.location.reload();
                } else {
                    alert('Simulation failed: ' + data.message);
                    this.disabled = false;
                    this.innerHTML = '<i class="bi bi-check2"></i> Approve';
                }
            } catch (e) {
                alert('Error: ' + e.message);
                this.disabled = false;
            }
        });
    });
});
</script>
@endsection
