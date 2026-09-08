@extends('layouts.app', ['title' => 'Menu Categories - Food Point POS'])

@section('content')
<div class="page-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
  <div>
    <h1 class="page-title">Menu Categories</h1>
    <nav class="breadcrumb small">
      <a href="{{ route('dashboard') }}" class="breadcrumb-item text-decoration-none">Home</a>
      <span class="breadcrumb-item active">Categories</span>
    </nav>
  </div>
  <div>
    <button type="button" class="btn btn-primary btn-sm px-3 py-2" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
      <i class="bi bi-plus-circle me-1"></i> Add Category
    </button>
  </div>
</div>

<div class="card border">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Category Name</th>
            <th>Slug</th>
            <th>Description</th>
            <th>Total Products</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($categories as $cat)
            <tr>
              <td class="fw-bold text-heading">{{ $cat->name }}</td>
              <td><span class="badge bg-secondary">{{ $cat->slug }}</span></td>
              <td class="small text-muted">{{ $cat->description ?: '-' }}</td>
              <td>{{ $cat->products_count }} items</td>
              <td>
                <span class="badge {{ $cat->is_active ? 'badge-soft-success' : 'badge-soft-danger' }}">
                  {{ $cat->is_active ? 'Active' : 'Inactive' }}
                </span>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="text-center py-4 text-muted">No categories created yet.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="{{ route('resources.categories.store') }}">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title fw-bold">Add Category</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Category Name</label>
            <input type="text" name="name" class="form-control" placeholder="e.g. Burgers, Hot Drinks" required>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Description</label>
            <input type="text" name="description" class="form-control" placeholder="Optional notes">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Category</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
