@extends('layouts.app')

@section('title', 'Configure Role: ' . $role->name)

@section('content')
<div class="row g-4 mb-4">
    <div class="col-12 d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1 small">
                    <li class="breadcrumb-item"><a href="{{ route('roles.index') }}">Roles & Permissions</a></li>
                    <li class="breadcrumb-item active">Configure Role</li>
                </ol>
            </nav>
            <h4 class="mb-1 fw-bold text-body-emphasis">Configure Role: {{ $role->name }}</h4>
            <p class="text-muted mb-0 small">Customize assigned permissions and security boundaries for this role.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary">
                <i class="ph-duotone ph-arrow-left me-1"></i> Back to Roles
            </a>
        </div>
    </div>
</div>

<form method="POST" action="{{ route('roles.update', $role->id) }}">
    @csrf
    @method('PUT')

    <!-- Role Information Card -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-header bg-transparent border-0 pt-3 px-4 pb-0 d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0"><i class="ph-duotone ph-identification-badge me-2 text-primary"></i> Role Details</h6>
            @if($role->isSystem())
                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                    <i class="ph-duotone ph-lock-key me-1"></i> System Role (Slug Protected)
                </span>
            @else
                <span class="badge bg-success-subtle text-success border border-success-subtle">
                    <i class="ph-duotone ph-sparkle me-1"></i> Custom Role
                </span>
            @endif
        </div>
        <div class="card-body p-4">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Role Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $role->name) }}" class="form-control @error('name') is-invalid @enderror" {{ $role->isSystem() ? 'readonly' : 'required' }}>
                    @if($role->isSystem())
                        <div class="form-text small text-muted">System default role names and slugs cannot be renamed.</div>
                    @endif
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Description</label>
                    <input type="text" name="description" value="{{ old('description', $role->description) }}" class="form-control @error('description') is-invalid @enderror" placeholder="Describe role responsibilities...">
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>

    <!-- Permissions Matrix Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
        <div>
            <h5 class="fw-bold mb-0">Module Permissions Matrix</h5>
            <p class="text-muted small mb-0">Toggle individual privileges or entire functional modules.</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-outline-primary" id="btnSelectAll">
                <i class="ph-duotone ph-check-square me-1"></i> Select All
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnDeselectAll">
                <i class="ph-duotone ph-square me-1"></i> Deselect All
            </button>
        </div>
    </div>

    <!-- Grouped Modules Grid -->
    <div class="row g-4 mb-4">
        @foreach($groupedPermissions as $moduleName => $permissions)
        @php
            $modulePermIds = $permissions->pluck('id')->toArray();
            $allChecked = count($modulePermIds) > 0 && empty(array_diff($modulePermIds, $assignedPermissionIds));
        @endphp
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-header bg-light bg-opacity-50 border-bottom py-2 px-3 d-flex justify-content-between align-items-center">
                    <div class="fw-bold d-flex align-items-center gap-2">
                        @switch($moduleName)
                            @case('POS')
                                <i class="ph-duotone ph-storefront text-primary"></i>
                                @break
                            @case('Orders')
                                <i class="ph-duotone ph-receipt text-success"></i>
                                @break
                            @case('Cash')
                                <i class="ph-duotone ph-vault text-warning"></i>
                                @break
                            @case('Restaurant')
                                <i class="ph-duotone ph-fork-knife text-danger"></i>
                                @break
                            @case('Delivery')
                                <i class="ph-duotone ph-moped text-info"></i>
                                @break
                            @case('Catalog')
                                <i class="ph-duotone ph-cookie text-secondary"></i>
                                @break
                            @case('Inventory')
                                <i class="ph-duotone ph-archive text-warning"></i>
                                @break
                            @case('Finance')
                                <i class="ph-duotone ph-coins text-success"></i>
                                @break
                            @case('Reports')
                                <i class="ph-duotone ph-chart-line-up text-primary"></i>
                                @break
                            @case('Admin')
                                <i class="ph-duotone ph-gear text-dark"></i>
                                @break
                            @default
                                <i class="ph-duotone ph-check-circle text-primary"></i>
                        @endswitch
                        <span>{{ $moduleName }}</span>
                        <span class="badge bg-secondary-subtle text-secondary rounded-pill px-2 small">{{ count($permissions) }}</span>
                    </div>
                    <div class="form-check form-switch m-0">
                        <input class="form-check-input module-toggle" type="checkbox" role="switch" id="moduleToggle_{{ Str::slug($moduleName) }}" data-module="{{ Str::slug($moduleName) }}" {{ $allChecked ? 'checked' : '' }}>
                        <label class="form-check-label small text-muted user-select-none" for="moduleToggle_{{ Str::slug($moduleName) }}">Select All</label>
                    </div>
                </div>
                <div class="card-body p-3">
                    <div class="row g-2">
                        @foreach($permissions as $perm)
                        @php
                            $isChecked = in_array($perm->id, old('permissions', $assignedPermissionIds));
                        @endphp
                        <div class="col-12 col-sm-6">
                            <label class="form-check d-flex align-items-start gap-2 p-2 border rounded-2 bg-body-tertiary bg-opacity-25 h-100 mb-0 cursor-pointer hover-bg-light" style="cursor: pointer;">
                                <input class="form-check-input flex-shrink-0 mt-1 perm-checkbox module-{{ Str::slug($moduleName) }}" type="checkbox" name="permissions[]" value="{{ $perm->id }}" id="perm_{{ $perm->id }}" {{ $isChecked ? 'checked' : '' }}>
                                <span class="form-check-label d-block text-start">
                                    <span class="fw-semibold d-block text-body small">{{ $perm->name }}</span>
                                    <code class="text-muted" style="font-size: 0.72rem;">{{ $perm->slug }}</code>
                                </span>
                            </label>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="d-flex justify-content-end gap-2 pb-5">
        <a href="{{ route('roles.index') }}" class="btn btn-light px-4">Cancel</a>
        <button type="submit" class="btn btn-primary px-4 d-flex align-items-center gap-2">
            <i class="ph-duotone ph-floppy-disk"></i> Save Permissions
        </button>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const permCheckboxes = document.querySelectorAll('.perm-checkbox');
    const moduleToggles = document.querySelectorAll('.module-toggle');

    // Global Select All
    document.getElementById('btnSelectAll')?.addEventListener('click', function () {
        permCheckboxes.forEach(cb => cb.checked = true);
        moduleToggles.forEach(mt => mt.checked = true);
    });

    // Global Deselect All
    document.getElementById('btnDeselectAll')?.addEventListener('click', function () {
        permCheckboxes.forEach(cb => cb.checked = false);
        moduleToggles.forEach(mt => mt.checked = false);
    });

    // Per-module toggle
    moduleToggles.forEach(toggle => {
        toggle.addEventListener('change', function () {
            const moduleSlug = this.dataset.module;
            const targetCheckboxes = document.querySelectorAll('.module-' + moduleSlug);
            targetCheckboxes.forEach(cb => cb.checked = toggle.checked);
        });
    });
});
</script>
@endsection
