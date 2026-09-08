@extends('layouts.app')

@section('title', 'System Audit Trail & Security Logs')

@section('content')
<div class="row g-4 mb-4">
    <div class="col-12 d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <h4 class="mb-1 fw-bold text-body-emphasis">System Audit Trail</h4>
            <p class="text-muted mb-0 small">Immutable security logs, user actions, financial journal triggers, and adjustments</p>
        </div>
        <div>
            <button onclick="window.print()" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
                <i class="bi bi-printer"></i> Print Audit Log
            </button>
        </div>
    </div>
</div>

<!-- Filter Bar -->
<div class="card border-0 shadow-sm rounded-3 mb-4">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('admin.audit-logs') }}" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small text-muted mb-1">Filter by Module</label>
                <select name="module" class="form-select form-select-sm">
                    <option value="">All Modules</option>
                    <option value="orders" {{ request('module') == 'orders' ? 'selected' : '' }}>Orders & POS</option>
                    <option value="inventory" {{ request('module') == 'inventory' ? 'selected' : '' }}>Inventory & Stock</option>
                    <option value="finance" {{ request('module') == 'finance' ? 'selected' : '' }}>Finance & Accounting</option>
                    <option value="cash" {{ request('module') == 'cash' ? 'selected' : '' }}>Cash Shifts & Drawer</option>
                    <option value="users" {{ request('module') == 'users' ? 'selected' : '' }}>User Accounts</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small text-muted mb-1">Performed by User</label>
                <select name="user_id" class="form-select form-select-sm">
                    <option value="">All Users</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }} ({{ $u->role }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm flex-fill">Apply Filter</button>
                <a href="{{ route('admin.audit-logs') }}" class="btn btn-light btn-sm">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-3">
    <div class="card-header bg-transparent border-0 pt-3 px-3 pb-0">
        <h6 class="fw-bold mb-0">Audit Events ({{ $logs->total() }})</h6>
    </div>
    <div class="card-body p-0 mt-2">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3" style="width: 180px;">Timestamp</th>
                        <th>User</th>
                        <th>Module</th>
                        <th>Action</th>
                        <th>Record ID</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $l)
                    <tr>
                        <td class="ps-3 text-muted small">{{ $l->created_at->format('Y-m-d H:i:s') }}</td>
                        <td>
                            <span class="fw-bold text-body">{{ $l->user->name ?? 'System Automated' }}</span>
                        </td>
                        <td>
                            <span class="badge bg-secondary-subtle text-secondary">{{ strtoupper($l->module) }}</span>
                        </td>
                        <td>
                            <code class="text-primary">{{ $l->action }}</code>
                        </td>
                        <td>
                            <span class="text-muted small">#{{ $l->record_id ?? '—' }}</span>
                        </td>
                        <td class="text-muted small"><code>{{ $l->ip_address ?? '127.0.0.1' }}</code></td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">No audit log entries recorded.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($logs->hasPages())
    <div class="card-footer bg-transparent border-0 px-3 py-2">
        {{ $logs->links() }}
    </div>
    @endif
</div>
@endsection
