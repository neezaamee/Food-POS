@extends('layouts.app', ['title' => 'Tables & Sections - Food Point POS'])

@section('content')
<div class="page-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
  <div>
    <h1 class="page-title">Dining Tables & Floor Plan</h1>
    <nav class="breadcrumb small">
      <a href="{{ route('dashboard') }}" class="breadcrumb-item text-decoration-none">Home</a>
      <span class="breadcrumb-item active">Tables</span>
    </nav>
  </div>
  <div class="d-flex align-items-center gap-2">
    <button type="button" class="btn btn-outline-secondary btn-sm px-3 py-2" data-bs-toggle="modal" data-bs-target="#addSectionModal">
      <i class="bi bi-folder-plus me-1"></i> Add Section
    </button>
    <button type="button" class="btn btn-primary btn-sm px-3 py-2" data-bs-toggle="modal" data-bs-target="#addTableModal">
      <i class="bi bi-plus-circle me-1"></i> Add Table
    </button>
  </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
  <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
  <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
  <ul class="mb-0 small ps-3">
    @foreach ($errors->all() as $error)
      <li>{{ $error }}</li>
    @endforeach
  </ul>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<!-- Table Status Legend -->
<div class="card border mb-4">
  <div class="card-body py-2 px-3 d-flex flex-wrap gap-4 align-items-center small">
    <div class="d-flex align-items-center gap-2">
      <span class="badge bg-success rounded-circle p-1" style="width: 12px; height: 12px;"> </span>
      <span class="text-muted">Available Table</span>
    </div>
    <div class="d-flex align-items-center gap-2">
      <span class="badge bg-danger rounded-circle p-1" style="width: 12px; height: 12px;"> </span>
      <span class="text-muted">Occupied / Dining</span>
    </div>
    <div class="d-flex align-items-center gap-2">
      <span class="badge bg-warning rounded-circle p-1" style="width: 12px; height: 12px;"> </span>
      <span class="text-muted">Reserved / Cleaning</span>
    </div>
  </div>
</div>

<!-- Sections and Tables Grid -->
@foreach ($sections as $section)
  <div class="card border mb-4">
    <div class="card-header bg-transparent py-3 d-flex align-items-center justify-content-between">
      <div>
        <h5 class="card-title mb-0 fs-6 fw-bold">{{ $section->name }}</h5>
        <div class="small text-muted">{{ $section->description ?: 'Dining Section' }}</div>
      </div>
      <div class="d-flex align-items-center gap-2">
        <span class="badge bg-secondary">{{ $section->tables->count() }} Tables</span>
        <div class="dropdown">
          <button class="btn btn-sm btn-outline-secondary p-1" type="button" data-bs-toggle="dropdown" title="Section Options">
            <i class="bi bi-three-dots-vertical"></i>
          </button>
          <ul class="dropdown-menu dropdown-menu-end shadow-sm">
            <li>
              <button type="button" class="dropdown-item small d-flex align-items-center gap-2 edit-section-btn"
                data-id="{{ $section->id }}"
                data-name="{{ $section->name }}"
                data-description="{{ $section->description }}"
                data-action="{{ route('restaurant.sections.update', $section->id) }}">
                <i class="bi bi-pencil text-primary"></i> Edit Section
              </button>
            </li>
            @if($section->tables->count() === 0)
              <li><hr class="dropdown-divider my-1"></li>
              <li>
                <form method="POST" action="{{ route('restaurant.sections.destroy', $section->id) }}" onsubmit="return confirm('Are you sure you want to delete section {{ $section->name }}?');">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="dropdown-item small text-danger d-flex align-items-center gap-2">
                    <i class="bi bi-trash"></i> Delete Section
                  </button>
                </form>
              </li>
            @endif
          </ul>
        </div>
      </div>
    </div>
    <div class="card-body p-4">
      <div class="row g-3">
        @forelse ($section->tables as $table)
          <div class="col-6 col-sm-4 col-md-3 col-xl-2">
            <div class="card h-100 p-3 border {{ $table->isOccupied() ? 'border-danger bg-danger-subtle' : 'border-success bg-surface' }} position-relative text-center shadow-sm">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="fw-bold fs-6 text-heading">{{ $table->table_number }}</span>
                <div class="d-flex align-items-center gap-1">
                  <span class="badge {{ $table->isOccupied() ? 'bg-danger' : ($table->status === 'available' ? 'bg-success' : 'bg-warning text-dark') }}" style="font-size: 0.65rem;">
                    {{ ucfirst($table->status) }}
                  </span>
                  <div class="dropdown">
                    <button class="btn btn-sm btn-light border-0 p-0 text-muted" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="width: 22px; height: 22px;">
                      <i class="bi bi-three-dots-vertical"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                      <li>
                        <button type="button" class="dropdown-item small d-flex align-items-center gap-2 edit-table-btn"
                          data-id="{{ $table->id }}"
                          data-table-number="{{ $table->table_number }}"
                          data-name="{{ $table->name }}"
                          data-section-id="{{ $table->section_id }}"
                          data-capacity="{{ $table->capacity }}"
                          data-status="{{ $table->status }}"
                          data-action="{{ route('restaurant.tables.update', $table->id) }}">
                          <i class="bi bi-pencil text-primary"></i> Edit Table
                        </button>
                      </li>
                      <li><hr class="dropdown-divider my-1"></li>
                      <li>
                        @if($table->isOccupied() || $table->active_order_id)
                          <span class="dropdown-item small text-muted disabled" title="Table is occupied">
                            <i class="bi bi-lock me-1"></i> Occupied (Locked)
                          </span>
                        @else
                          <form method="POST" action="{{ route('restaurant.tables.destroy', $table->id) }}" onsubmit="return confirm('Are you sure you want to delete Table {{ $table->name }} ({{ $table->table_number }})?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="dropdown-item small text-danger d-flex align-items-center gap-2">
                              <i class="bi bi-trash"></i> Delete Table
                            </button>
                          </form>
                        @endif
                      </li>
                    </ul>
                  </div>
                </div>
              </div>
              <div class="fw-semibold small text-truncate" title="{{ $table->name }}">{{ $table->name }}</div>
              <div class="small text-muted mb-3" style="font-size: 0.75rem;">Capacity: {{ $table->capacity }} Pax</div>

              @if ($table->isOccupied() && $table->active_order_id)
                <a href="{{ route('pos.index', ['orderId' => $table->active_order_id]) }}" class="btn btn-sm btn-danger py-1" style="font-size: 0.75rem;">
                  <i class="bi bi-folder2-open me-1"></i> Order #{{ $table->activeOrder?->order_number }}
                </a>
              @else
                <a href="{{ route('pos.index') }}" class="btn btn-sm btn-outline-success py-1" style="font-size: 0.75rem;">
                  <i class="bi bi-plus me-1"></i> Open Order
                </a>
              @endif
            </div>
          </div>
        @empty
          <div class="col-12 text-center py-3 text-muted">No tables configured in this section yet.</div>
        @endforelse
      </div>
    </div>
  </div>
@endforeach

<!-- ADD SECTION MODAL -->
<div class="modal fade" id="addSectionModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="{{ route('restaurant.sections.store') }}">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title fw-bold">Add Table Section</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Section Name</label>
            <input type="text" name="name" class="form-control" placeholder="e.g. Terrace, Family Hall, VIP" required>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Description</label>
            <input type="text" name="description" class="form-control" placeholder="Optional notes">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Section</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- EDIT SECTION MODAL -->
<div class="modal fade" id="editSectionModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form id="editSectionForm" method="POST" action="">
        @csrf
        @method('PUT')
        <div class="modal-header">
          <h5 class="modal-title fw-bold">Edit Table Section</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Section Name</label>
            <input type="text" name="name" id="editSectionName" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Description</label>
            <input type="text" name="description" id="editSectionDescription" class="form-control" placeholder="Optional notes">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Update Section</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ADD TABLE MODAL -->
<div class="modal fade" id="addTableModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="{{ route('restaurant.tables.store') }}">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title fw-bold">Add Restaurant Table</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Table Section</label>
            <select name="section_id" class="form-select" required>
              @foreach ($sections as $s)
                <option value="{{ $s->id }}">{{ $s->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label small fw-semibold">Table # / Code</label>
              <input type="text" name="table_number" class="form-control" placeholder="e.g. T-09" required>
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold">Seating Capacity</label>
              <input type="number" name="capacity" class="form-control" value="4" min="1" required>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Display Name</label>
            <input type="text" name="name" class="form-control" placeholder="e.g. Table 09" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Add Table</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- EDIT TABLE MODAL -->
<div class="modal fade" id="editTableModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form id="editTableForm" method="POST" action="">
        @csrf
        @method('PUT')
        <div class="modal-header">
          <h5 class="modal-title fw-bold">Edit Restaurant Table</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Table Section</label>
            <select name="section_id" id="editTableSectionId" class="form-select" required>
              @foreach ($sections as $s)
                <option value="{{ $s->id }}">{{ $s->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label small fw-semibold">Table # / Code</label>
              <input type="text" name="table_number" id="editTableNumber" class="form-control" required>
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold">Seating Capacity</label>
              <input type="number" name="capacity" id="editTableCapacity" class="form-control" min="1" required>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Display Name</label>
            <input type="text" name="name" id="editTableName" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Table Status</label>
            <select name="status" id="editTableStatus" class="form-select" required>
              <option value="available">Available</option>
              <option value="occupied">Occupied</option>
              <option value="reserved">Reserved</option>
              <option value="cleaning">Cleaning</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Update Table</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  // Edit Table Modal Population
  document.querySelectorAll('.edit-table-btn').forEach(button => {
    button.addEventListener('click', function () {
      const form = document.getElementById('editTableForm');
      form.action = this.dataset.action;
      document.getElementById('editTableSectionId').value = this.dataset.sectionId;
      document.getElementById('editTableNumber').value = this.dataset.tableNumber;
      document.getElementById('editTableName').value = this.dataset.name;
      document.getElementById('editTableCapacity').value = this.dataset.capacity;
      document.getElementById('editTableStatus').value = this.dataset.status;

      new bootstrap.Modal(document.getElementById('editTableModal')).show();
    });
  });

  // Edit Section Modal Population
  document.querySelectorAll('.edit-section-btn').forEach(button => {
    button.addEventListener('click', function () {
      const form = document.getElementById('editSectionForm');
      form.action = this.dataset.action;
      document.getElementById('editSectionName').value = this.dataset.name;
      document.getElementById('editSectionDescription').value = this.dataset.description || '';

      new bootstrap.Modal(document.getElementById('editSectionModal')).show();
    });
  });
});
</script>
@endpush
