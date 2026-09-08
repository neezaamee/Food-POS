@extends('layouts.app')

@section('title', 'FBR Digital Invoicing Configuration')

@section('content')
<div class="row g-4 mb-4">
    <div class="col-12 d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <h4 class="mb-1 fw-bold text-body-emphasis">FBR Digital Invoicing Integration</h4>
            <p class="text-muted mb-0 small">Federal Board of Revenue POS real-time fiscal synchronization & invoice logging</p>
        </div>
        <div>
            @if($setting->is_enabled)
                <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 fs-6">
                    <i class="bi bi-broadcast me-1"></i> FBR Invoicing Live / Active
                </span>
            @else
                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-3 py-2 fs-6">
                    <i class="bi bi-pause-circle me-1"></i> FBR Invoicing Offline / Sandbox
                </span>
            @endif
        </div>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row g-4 mb-4">
    <!-- Configuration Form -->
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
                <h6 class="fw-bold mb-0">POS Terminal Credentials</h6>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('admin.fbr.update') }}">
                    @csrf
                    
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="fbrEnabled" name="is_enabled" value="1" {{ $setting->is_enabled ? 'checked' : '' }}>
                            <label class="form-check-label fw-bold small" for="fbrEnabled">Enable Automatic FBR Synchronization</label>
                        </div>
                        <div class="form-text small">When enabled, finalized orders submit to FBR and receive an official FBR Invoice Number & QR code.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-medium">FBR POS Identification (POS ID)</label>
                        <input type="text" name="pos_id" class="form-control" value="{{ $setting->pos_id }}" required placeholder="e.g. 100293">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-medium">Integration Mode</label>
                        <select name="mode" class="form-select" required>
                            <option value="sandbox" {{ $setting->mode == 'sandbox' ? 'selected' : '' }}>Sandbox / Test Environment</option>
                            <option value="live" {{ $setting->mode == 'live' ? 'selected' : '' }}>Live Production Gateway</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-medium">FBR API Endpoint URL</label>
                        <input type="url" name="api_url" class="form-control" value="{{ $setting->api_url }}" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-medium">Authorization Bearer Token</label>
                        <input type="password" name="bearer_token" class="form-control" value="{{ $setting->bearer_token }}" placeholder="Enter FBR bearer token">
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-shield-check me-1"></i> Update FBR Configuration
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Recent Submissions Log -->
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
                <h6 class="fw-bold mb-0">Recent Fiscal Invoicing Submissions</h6>
            </div>
            <div class="card-body p-0 mt-2">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Order #</th>
                                <th>FBR Invoice #</th>
                                <th>Status</th>
                                <th>Time</th>
                                <th class="pe-3">Response Code</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($submissions as $sub)
                            <tr>
                                <td class="ps-3 fw-bold">
                                    <a href="{{ route('orders.show', $sub->order_id) }}">
                                        {{ $sub->order->order_number ?? 'Order #' . $sub->order_id }}
                                    </a>
                                </td>
                                <td><code>{{ $sub->fbr_invoice_number ?? 'Pending' }}</code></td>
                                <td>
                                    @if($sub->status === 'success')
                                        <span class="badge bg-success-subtle text-success">Success</span>
                                    @elseif($sub->status === 'failed')
                                        <span class="badge bg-danger-subtle text-danger">Failed</span>
                                    @else
                                        <span class="badge bg-warning-subtle text-warning">Pending</span>
                                    @endif
                                </td>
                                <td class="text-muted small">{{ $sub->created_at->format('M d, H:i') }}</td>
                                <td class="pe-3 small text-muted">{{ $sub->response_code ?? '200 OK' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">No FBR fiscal submissions logged yet.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($submissions->hasPages())
            <div class="card-footer bg-transparent border-0 px-3 py-2">
                {{ $submissions->links() }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
