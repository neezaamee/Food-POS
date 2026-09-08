@extends('layouts.app')

@section('title', 'System & Restaurant Settings')

@section('content')
<div class="row g-4 mb-4">
    <div class="col-12">
        <h4 class="mb-1 fw-bold text-body-emphasis">System Settings</h4>
        <p class="text-muted mb-0 small">Configure restaurant branding, receipt headers, tax percentages, and default operations</p>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    <strong>Please check the errors below:</strong>
    <ul class="mb-0 mt-1 small">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="card border-0 shadow-sm rounded-3">
    <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
        <h6 class="fw-bold mb-0">General Restaurant Configuration</h6>
    </div>
    <div class="card-body p-4">
        <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data">
            @csrf
            
            <!-- Business Logo Branding Section -->
            <div class="row g-3 mb-4 p-3 bg-light-subtle rounded-3 border">
                <div class="col-md-3 text-center d-flex flex-column align-items-center justify-content-center border-end">
                    <label class="form-label small fw-bold text-muted mb-2">Current Brand Logo</label>
                    <div class="p-2 bg-white rounded border d-flex align-items-center justify-content-center shadow-sm" style="min-height: 80px; width: 100%; max-width: 200px;">
                        <img src="{{ \App\Models\SystemSetting::logoUrl() }}?v={{ time() }}" alt="Current Logo" style="max-height: 60px; max-width: 100%; object-fit: contain;">
                    </div>
                    @if(!empty($settings['restaurant_logo']))
                        <div class="form-check form-check-inline mt-2">
                            <input class="form-check-input" type="checkbox" name="remove_logo" value="1" id="removeLogoCheck">
                            <label class="form-check-label text-danger small" for="removeLogoCheck">Reset to default</label>
                        </div>
                    @endif
                </div>
                <div class="col-md-9 d-flex flex-column justify-content-center">
                    <label class="form-label small fw-medium">Upload New Business Logo</label>
                    <input type="file" name="restaurant_logo" class="form-control form-control-sm mb-1" accept="image/png,image/jpeg,image/webp,image/svg+xml">
                    <div class="form-text small text-muted">
                        <i class="bi bi-info-circle me-1"></i> Transparent PNG, WebP, or SVG recommended. Max file size: 2MB. This logo will appear across POS, thermal slips, A4 invoices, and system navigation.
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label small fw-medium">Restaurant Brand / Business Name</label>
                    <input type="text" name="restaurant_name" class="form-control" value="{{ $settings['restaurant_name'] ?? ($settings['business_name'] ?? 'Food Point POS') }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-medium">Tagline / Subheading</label>
                    <input type="text" name="tagline" class="form-control" value="{{ $settings['tagline'] ?? 'Taste the Freshness' }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-medium">Official Contact Phone</label>
                    <input type="text" name="restaurant_phone" class="form-control" value="{{ $settings['restaurant_phone'] ?? ($settings['business_phone'] ?? '+92 42 111-366-376') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-medium">Official Support Email</label>
                    <input type="email" name="restaurant_email" class="form-control" value="{{ $settings['restaurant_email'] ?? 'support@foodpoint.com' }}">
                </div>
                <div class="col-md-12">
                    <label class="form-label small fw-medium">Physical Address (Prints on thermal slip & A4)</label>
                    <input type="text" name="restaurant_address" class="form-control" value="{{ $settings['restaurant_address'] ?? ($settings['business_address'] ?? 'Plot 14-B, Commercial Zone, Phase 5 DHA, Lahore') }}">
                </div>
            </div>

            <h6 class="fw-bold border-top pt-4 mb-3">Taxes & Financial Rates</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label small fw-medium">Currency Symbol / Code</label>
                    <input type="text" name="currency" class="form-control" value="{{ $settings['currency'] ?? 'Rs.' }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-medium">Standard Sales Tax / GST (%)</label>
                    <input type="number" step="0.01" name="tax_rate" class="form-control" value="{{ $settings['tax_rate'] ?? '5.00' }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-medium">NTN Number</label>
                    <input type="text" name="ntn_number" class="form-control" value="{{ $settings['ntn_number'] ?? '8192841-7' }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-medium">STRN / PRA Number</label>
                    <input type="text" name="strn_number" class="form-control" value="{{ $settings['strn_number'] ?? '3277876123456' }}">
                </div>
            </div>

            <h6 class="fw-bold border-top pt-4 mb-3">POS Terminal Preferences</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label small fw-medium">Default Channel</label>
                    <select name="default_order_type" class="form-select">
                        <option value="TAKEAWAY" {{ ($settings['default_order_type'] ?? '') == 'TAKEAWAY' ? 'selected' : '' }}>Takeaway</option>
                        <option value="DINE_IN" {{ ($settings['default_order_type'] ?? '') == 'DINE_IN' ? 'selected' : '' }}>Dine-In</option>
                        <option value="DELIVERY" {{ ($settings['default_order_type'] ?? '') == 'DELIVERY' ? 'selected' : '' }}>Delivery</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-medium">Receipt Thermal Footer Note</label>
                    <input type="text" name="invoice_footer_note" class="form-control" value="{{ $settings['invoice_footer_note'] ?? 'Thank you for dining with us! Please visit again.' }}">
                </div>
            </div>

            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-floppy-fill me-1"></i> Save System Settings
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
