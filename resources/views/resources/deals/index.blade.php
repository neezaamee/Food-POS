@extends('layouts.app', ['title' => 'Packages & Deals - Food Point POS'])

@section('content')
<div class="page-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
  <div>
    <h1 class="page-title"><i class="ph-duotone ph-package me-2 text-primary"></i> Packages & Deals</h1>
    <nav class="breadcrumb small mb-0">
      <a href="{{ route('dashboard') }}" class="breadcrumb-item text-decoration-none">Home</a>
      <span class="breadcrumb-item">Catalog & Resources</span>
      <span class="breadcrumb-item active">Packages & Deals</span>
    </nav>
  </div>
  <div>
    <button type="button" class="btn btn-primary btn-sm px-3 py-2 fw-semibold" data-bs-toggle="modal" data-bs-target="#createDealModal">
      <i class="bi bi-plus-circle me-1"></i> Create Package / Deal
    </button>
  </div>
</div>

<!-- Quick Stats Bar -->
<div class="row g-3 mb-4">
  <div class="col-sm-6 col-lg-3">
    <div class="card border h-100 shadow-sm">
      <div class="card-body p-3 d-flex align-items-center gap-3">
        <div class="rounded-3 bg-primary-subtle text-primary p-2 fs-3">
          <i class="ph-duotone ph-package"></i>
        </div>
        <div>
          <div class="text-muted small">Total Packages</div>
          <div class="fw-bold fs-4">{{ $totalDeals }}</div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-lg-3">
    <div class="card border h-100 shadow-sm">
      <div class="card-body p-3 d-flex align-items-center gap-3">
        <div class="rounded-3 bg-success-subtle text-success p-2 fs-3">
          <i class="ph-duotone ph-check-circle"></i>
        </div>
        <div>
          <div class="text-muted small">Active in POS</div>
          <div class="fw-bold fs-4 text-success">{{ $activeDeals }}</div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-lg-6">
    <div class="card border h-100 bg-light-subtle shadow-sm">
      <div class="card-body p-3 d-flex align-items-center justify-content-between">
        <div>
          <div class="fw-bold text-dark mb-1"><i class="bi bi-lightning-charge-fill text-warning me-1"></i> Automated POS & Inventory Integration</div>
          <div class="small text-muted">Deals automatically display under <strong>"Packages & Deals"</strong> in POS Live, and deduct constituent item stock when ordered!</div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Search & Filter Card -->
<div class="card border mb-4 shadow-sm">
  <div class="card-body p-3">
    <form method="GET" action="{{ route('resources.deals.index') }}" class="row g-2 align-items-center">
      <div class="col-md-6">
        <div class="input-group input-group-sm">
          <span class="input-group-text bg-surface"><i class="bi bi-search"></i></span>
          <input type="text" name="search" class="form-control" placeholder="Search by deal name, code, description..." value="{{ request('search') }}">
        </div>
      </div>
      <div class="col-md-3">
        <select name="status" class="form-select form-select-sm">
          <option value="">-- All Statuses --</option>
          <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
          <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
        </select>
      </div>
      <div class="col-md-3 d-flex gap-2">
        <button type="submit" class="btn btn-sm btn-primary flex-grow-1">Filter</button>
        <a href="{{ route('resources.deals.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
      </div>
    </form>
  </div>
</div>

<!-- Deals List -->
<div class="card border shadow-sm">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th style="min-width: 180px;">Package / Deal</th>
            <th>Code</th>
            <th style="min-width: 250px;">Included Items & Quantities</th>
            <th>Original Value</th>
            <th>Deal Price</th>
            <th>Customer Savings</th>
            <th>Status</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($deals as $deal)
            @php
              $origVal = $deal->originalTotalValue();
              $savings = $deal->savingsAmount();
            @endphp
            <tr>
              <td>
                <div class="fw-bold text-heading fs-6">{{ $deal->name }}</div>
                @if ($deal->description)
                  <div class="small text-muted text-truncate" style="max-width: 220px;" title="{{ $deal->description }}">{{ $deal->description }}</div>
                @endif
              </td>
              <td>
                <span class="badge bg-dark font-monospace">{{ $deal->code }}</span>
              </td>
              <td>
                <div class="d-flex flex-wrap gap-1">
                  @forelse ($deal->items as $dItem)
                    <span class="badge bg-light text-dark border px-2 py-1" style="font-size: 0.78rem;">
                      <strong class="text-primary">{{ (int)$dItem->quantity }}x</strong> {{ $dItem->product?->name ?? 'Unknown Item' }}
                    </span>
                  @empty
                    <span class="small text-muted fst-italic">No items specified</span>
                  @endforelse
                </div>
              </td>
              <td>
                <div class="small text-muted text-decoration-line-through">
                  Rs. {{ number_format($origVal, 2) }}
                </div>
              </td>
              <td>
                <div class="fw-bold text-success fs-6">
                  Rs. {{ number_format($deal->sale_price, 2) }}
                </div>
              </td>
              <td>
                @if ($savings > 0)
                  <span class="badge bg-success-subtle text-success border border-success-subtle">
                    Save Rs. {{ number_format($savings, 0) }}
                  </span>
                @else
                  <span class="badge bg-secondary-subtle text-secondary">Combo Pricing</span>
                @endif
              </td>
              <td>
                <form method="POST" action="{{ route('resources.deals.toggle-status', $deal->id) }}" class="d-inline">
                  @csrf
                  <button type="submit" class="badge border-0 {{ $deal->is_active ? 'bg-success' : 'bg-secondary' }} cursor-pointer" title="Click to toggle status">
                    {{ $deal->is_active ? 'Active' : 'Inactive' }}
                  </button>
                </form>
              </td>
              <td class="text-end">
                <div class="btn-group btn-group-sm">
                  <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editDealModal{{ $deal->id }}" title="Edit Deal">
                    <i class="bi bi-pencil"></i>
                  </button>
                  <form method="POST" action="{{ route('resources.deals.destroy', $deal->id) }}" onsubmit="return confirm('Are you sure you want to delete this package deal? This will also remove it from POS Live.')" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger" title="Delete Deal">
                      <i class="bi bi-trash"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>

            <!-- EDIT DEAL MODAL -->
            <div class="modal fade" id="editDealModal{{ $deal->id }}" tabindex="-1" aria-hidden="true">
              <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                  <form method="POST" action="{{ route('resources.deals.update', $deal->id) }}">
                    @csrf
                    @method('PUT')
                    <div class="modal-header bg-primary text-white py-2 px-3">
                      <h6 class="modal-title fw-bold mb-0">
                        <i class="ph-duotone ph-pencil me-2"></i> Edit Package / Deal: {{ $deal->name }}
                      </h6>
                      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                      <div class="row g-3 mb-3">
                        <div class="col-md-8">
                          <label class="form-label small fw-semibold text-muted mb-1">Deal Name <span class="text-danger">*</span></label>
                          <input type="text" name="name" class="form-control form-control-sm" value="{{ $deal->name }}" required>
                        </div>
                        <div class="col-md-4">
                          <label class="form-label small fw-semibold text-muted mb-1">Code / SKU <span class="text-danger">*</span></label>
                          <input type="text" name="code" class="form-control form-control-sm text-uppercase" value="{{ $deal->code }}" required>
                        </div>
                        <div class="col-md-6">
                          <label class="form-label small fw-semibold text-muted mb-1">Package Sale Price (Rs.) <span class="text-danger">*</span></label>
                          <input type="number" step="0.01" min="0" name="sale_price" class="form-control form-control-sm fw-bold text-success" value="{{ $deal->sale_price }}" required>
                        </div>
                        <div class="col-md-6">
                          <label class="form-label small fw-semibold text-muted mb-1">Estimated Cost (Rs.)</label>
                          <input type="number" step="0.01" min="0" name="cost_price" class="form-control form-control-sm" value="{{ $deal->cost_price }}">
                        </div>
                        <div class="col-12">
                          <label class="form-label small fw-semibold text-muted mb-1">Description</label>
                          <textarea name="description" class="form-control form-control-sm" rows="2">{{ $deal->description }}</textarea>
                        </div>
                      </div>

                      <div class="border-top pt-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                          <h6 class="fw-bold mb-0 text-heading"><i class="bi bi-list-check me-1"></i> Included Items in this Deal</h6>
                          <button type="button" class="btn btn-sm btn-outline-primary" onclick="addEditDealRow({{ $deal->id }})">
                            <i class="bi bi-plus-lg me-1"></i> Add Another Item
                          </button>
                        </div>

                        <div class="table-responsive">
                          <table class="table table-sm align-middle mb-0" id="editDealItemsTable{{ $deal->id }}">
                            <thead class="table-light">
                              <tr>
                                <th>Select Food Item</th>
                                <th style="width: 130px;">Quantity</th>
                                <th style="width: 50px;"></th>
                              </tr>
                            </thead>
                            <tbody>
                              @foreach ($deal->items as $idx => $dItem)
                                <tr>
                                  <td>
                                    <select name="items[{{ $idx }}][product_id]" class="form-select form-select-sm" required>
                                      @foreach ($availableProducts as $prod)
                                        <option value="{{ $prod->id }}" {{ $dItem->product_id == $prod->id ? 'selected' : '' }}>
                                          {{ $prod->name }} (Rs. {{ number_format($prod->sale_price) }})
                                        </option>
                                      @endforeach
                                    </select>
                                  </td>
                                  <td>
                                    <input type="number" step="0.5" min="0.5" name="items[{{ $idx }}][quantity]" class="form-control form-control-sm text-center" value="{{ (float)$dItem->quantity }}" required>
                                  </td>
                                  <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-link text-danger p-0" onclick="this.closest('tr').remove()" title="Remove item">
                                      <i class="bi bi-x-circle fs-5"></i>
                                    </button>
                                  </td>
                                </tr>
                              @endforeach
                            </tbody>
                          </table>
                        </div>
                      </div>
                    </div>
                    <div class="modal-footer py-2 px-3 bg-light">
                      <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                      <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold">
                        <i class="bi bi-check2 me-1"></i> Update Deal
                      </button>
                    </div>
                  </form>
                </div>
              </div>
            </div>
          @empty
            <tr>
              <td colspan="8" class="text-center py-5 text-muted">
                <i class="ph-duotone ph-package fs-1 mb-2 opacity-50 d-block"></i>
                <h5>No Packages or Deals found</h5>
                <p class="small mb-3">Create combos and meal deals from your food items to boost sales!</p>
                <button type="button" class="btn btn-primary btn-sm px-3" data-bs-toggle="modal" data-bs-target="#createDealModal">
                  <i class="bi bi-plus-circle me-1"></i> Create First Package
                </button>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if ($deals->hasPages())
      <div class="p-3 border-top">
        {{ $deals->links() }}
      </div>
    @endif
  </div>
</div>

<!-- CREATE DEAL MODAL -->
<div class="modal fade" id="createDealModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <form method="POST" action="{{ route('resources.deals.store') }}" id="createDealForm">
        @csrf
        <div class="modal-header bg-primary text-white py-2 px-3">
          <h6 class="modal-title fw-bold mb-0">
            <i class="ph-duotone ph-package me-2 fs-5"></i> Create New Package / Deal
          </h6>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4">
          <div class="row g-3 mb-3">
            <div class="col-md-8">
              <label class="form-label small fw-semibold text-muted mb-1">Deal / Package Name <span class="text-danger">*</span></label>
              <input type="text" name="name" class="form-control form-control-sm" placeholder="e.g. Student Meal Combo, Family Feast Deal 1" required>
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold text-muted mb-1">Code / SKU <span class="text-danger">*</span></label>
              <input type="text" name="code" class="form-control form-control-sm text-uppercase" placeholder="e.g. DEAL-01, PKG-101" required>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold text-muted mb-1">Deal Selling Price (Rs.) <span class="text-danger">*</span></label>
              <div class="input-group input-group-sm">
                <span class="input-group-text fw-semibold">Rs.</span>
                <input type="number" step="0.01" min="0" name="sale_price" id="createDealSalePrice" class="form-control form-control-sm fw-bold text-success text-end" placeholder="0.00" required oninput="recalcDealSummary()">
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold text-muted mb-1">Package Cost Price (Rs.) <span class="small text-muted">(Auto or Custom)</span></label>
              <div class="input-group input-group-sm">
                <span class="input-group-text">Rs.</span>
                <input type="number" step="0.01" min="0" name="cost_price" id="createDealCostPrice" class="form-control form-control-sm text-end" placeholder="Leave blank to auto-calculate">
              </div>
            </div>
            <div class="col-12">
              <label class="form-label small fw-semibold text-muted mb-1">Description / Combo Summary</label>
              <textarea name="description" class="form-control form-control-sm" rows="2" placeholder="e.g. 1 Zinger Burger + 4 Spicy Wings + 1 Regular Fries + 1 Soft Drink 500ml"></textarea>
            </div>
          </div>

          <!-- Component Items Builder -->
          <div class="border-top pt-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <div>
                <h6 class="fw-bold mb-0 text-heading"><i class="bi bi-basket3 me-1 text-primary"></i> Select Items for this Package</h6>
                <div class="small text-muted">Add all the food items and quantities that make up this combo.</div>
              </div>
              <button type="button" class="btn btn-sm btn-outline-primary" onclick="addCreateDealRow()">
                <i class="bi bi-plus-lg me-1"></i> Add Another Item
              </button>
            </div>

            <div class="table-responsive">
              <table class="table table-sm align-middle mb-0" id="createDealItemsTable">
                <thead class="table-light">
                  <tr>
                    <th>Food Item</th>
                    <th style="width: 130px;">Quantity</th>
                    <th style="width: 50px;"></th>
                  </tr>
                </thead>
                <tbody id="createDealItemsTbody">
                  <tr>
                    <td>
                      <select name="items[0][product_id]" class="form-select form-select-sm deal-product-select" required onchange="recalcDealSummary()">
                        <option value="">-- Choose Food Item --</option>
                        @foreach ($availableProducts as $prod)
                          <option value="{{ $prod->id }}" data-price="{{ $prod->sale_price }}" data-cost="{{ $prod->cost_price }}">
                            {{ $prod->name }} (Rs. {{ number_format($prod->sale_price) }})
                          </option>
                        @endforeach
                      </select>
                    </td>
                    <td>
                      <input type="number" step="0.5" min="0.5" name="items[0][quantity]" class="form-control form-control-sm text-center deal-qty-input" value="1" required oninput="recalcDealSummary()">
                    </td>
                    <td class="text-center">
                      <button type="button" class="btn btn-sm btn-link text-danger p-0" onclick="removeCreateRow(this)" title="Remove item">
                        <i class="bi bi-x-circle fs-5"></i>
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Live Calculation Summary Pill -->
            <div class="bg-light-subtle border rounded p-2 mt-3 d-flex flex-wrap align-items-center justify-content-between small">
              <div>
                <span class="text-muted">Total Component Value:</span>
                <strong class="ms-1" id="lblOriginalValue">Rs. 0.00</strong>
              </div>
              <div>
                <span class="text-muted">Customer Savings:</span>
                <strong class="text-success ms-1" id="lblCustomerSavings">Rs. 0.00</strong>
              </div>
              <div class="text-muted fst-italic">
                Automatically added to Live POS upon save
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer py-2 px-3 bg-light d-flex justify-content-between">
          <span class="small text-muted"><i class="bi bi-info-circle me-1"></i> Syncs instantly to POS catalog</span>
          <div class="d-flex gap-2">
            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold">
              <i class="bi bi-check2 me-1"></i> Save Package / Deal
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  let createRowIdx = 1;

  function addCreateDealRow() {
    const tbody = document.getElementById('createDealItemsTbody');
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td>
        <select name="items[${createRowIdx}][product_id]" class="form-select form-select-sm deal-product-select" required onchange="recalcDealSummary()">
          <option value="">-- Choose Food Item --</option>
          @foreach ($availableProducts as $prod)
            <option value="{{ $prod->id }}" data-price="{{ $prod->sale_price }}" data-cost="{{ $prod->cost_price }}">
              {{ $prod->name }} (Rs. {{ number_format($prod->sale_price) }})
            </option>
          @endforeach
        </select>
      </td>
      <td>
        <input type="number" step="0.5" min="0.5" name="items[${createRowIdx}][quantity]" class="form-control form-control-sm text-center deal-qty-input" value="1" required oninput="recalcDealSummary()">
      </td>
      <td class="text-center">
        <button type="button" class="btn btn-sm btn-link text-danger p-0" onclick="removeCreateRow(this)" title="Remove item">
          <i class="bi bi-x-circle fs-5"></i>
        </button>
      </td>
    `;
    tbody.appendChild(tr);
    createRowIdx++;
    recalcDealSummary();
  }

  function removeCreateRow(btn) {
    const tbody = document.getElementById('createDealItemsTbody');
    if (tbody.children.length > 1) {
      btn.closest('tr').remove();
      recalcDealSummary();
    } else {
      alert('A deal must contain at least 1 item.');
    }
  }

  function recalcDealSummary() {
    let totalValue = 0;
    const selects = document.querySelectorAll('#createDealItemsTbody .deal-product-select');
    const qtys = document.querySelectorAll('#createDealItemsTbody .deal-qty-input');

    selects.forEach((sel, i) => {
      const selectedOpt = sel.options[sel.selectedIndex];
      if (selectedOpt && selectedOpt.dataset.price) {
        const price = parseFloat(selectedOpt.dataset.price) || 0;
        const qty = parseFloat(qtys[i]?.value) || 1;
        totalValue += (price * qty);
      }
    });

    const salePrice = parseFloat(document.getElementById('createDealSalePrice').value) || 0;
    const savings = Math.max(0, totalValue - salePrice);

    document.getElementById('lblOriginalValue').textContent = 'Rs. ' + totalValue.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('lblCustomerSavings').textContent = 'Rs. ' + savings.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
  }

  function addEditDealRow(dealId) {
    const tbody = document.querySelector(`#editDealItemsTable${dealId} tbody`);
    const newIdx = tbody.children.length;
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td>
        <select name="items[${newIdx}][product_id]" class="form-select form-select-sm" required>
          <option value="">-- Choose Food Item --</option>
          @foreach ($availableProducts as $prod)
            <option value="{{ $prod->id }}">
              {{ $prod->name }} (Rs. {{ number_format($prod->sale_price) }})
            </option>
          @endforeach
        </select>
      </td>
      <td>
        <input type="number" step="0.5" min="0.5" name="items[${newIdx}][quantity]" class="form-control form-control-sm text-center" value="1" required>
      </td>
      <td class="text-center">
        <button type="button" class="btn btn-sm btn-link text-danger p-0" onclick="this.closest('tr').remove()" title="Remove item">
          <i class="bi bi-x-circle fs-5"></i>
        </button>
      </td>
    `;
    tbody.appendChild(tr);
  }
</script>
@endsection
