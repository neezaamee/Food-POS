@extends('layouts.app', ['title' => 'Purchases & Procurement - Food Point POS'])

@section('content')
<div class="page-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
  <div>
    <h1 class="page-title">Purchases & Procurement</h1>
    <nav class="breadcrumb small">
      <a href="{{ route('dashboard') }}" class="breadcrumb-item text-decoration-none">Home</a>
      <a href="{{ route('inventory.overview') }}" class="breadcrumb-item text-decoration-none">Inventory</a>
      <span class="breadcrumb-item active">Purchases</span>
    </nav>
  </div>
  <div class="d-flex align-items-center gap-2">
    <button type="button" class="btn btn-outline-primary btn-sm px-3 py-2" data-bs-toggle="modal" data-bs-target="#quickProductModal">
      <i class="bi bi-box-seam me-1"></i> + New Material / Product
    </button>
    <button type="button" class="btn btn-primary btn-sm px-3 py-2" data-bs-toggle="modal" data-bs-target="#addPurchaseModal">
      <i class="bi bi-plus-circle me-1"></i> New Purchase Bill
    </button>
  </div>
</div>

<!-- KPI Summary Cards -->
<div class="row g-3 mb-4">
  <div class="col-sm-6 col-lg-3">
    <div class="card border shadow-sm h-100">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="text-muted small text-uppercase fw-bold">Total Procurement Value</div>
            <h3 class="fw-bold mb-0 text-primary mt-1">Rs. {{ number_format($totalPurchasesValue, 2) }}</h3>
          </div>
          <div class="bg-primary-subtle text-primary p-2 rounded">
            <i class="bi bi-cash-coin fs-4"></i>
          </div>
        </div>
        <div class="small text-muted mt-2">All recorded purchase orders</div>
      </div>
    </div>
  </div>

  <div class="col-sm-6 col-lg-3">
    <div class="card border shadow-sm h-100">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="text-muted small text-uppercase fw-bold">Purchase Orders</div>
            <h3 class="fw-bold mb-0 text-success mt-1">{{ $totalPurchasesCount }}</h3>
          </div>
          <div class="bg-success-subtle text-success p-2 rounded">
            <i class="bi bi-receipt fs-4"></i>
          </div>
        </div>
        <div class="small text-muted mt-2">Total PO bills issued</div>
      </div>
    </div>
  </div>

  <div class="col-sm-6 col-lg-3">
    <div class="card border shadow-sm h-100">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="text-muted small text-uppercase fw-bold">Active Suppliers</div>
            <h3 class="fw-bold mb-0 text-info mt-1">{{ $uniqueSuppliersCount }}</h3>
          </div>
          <div class="bg-info-subtle text-info p-2 rounded">
            <i class="bi bi-truck fs-4"></i>
          </div>
        </div>
        <div class="small text-muted mt-2">Vendors & distributors</div>
      </div>
    </div>
  </div>

  <div class="col-sm-6 col-lg-3">
    <div class="card border shadow-sm h-100">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="text-muted small text-uppercase fw-bold">Purchasable Items</div>
            <h3 class="fw-bold mb-0 text-dark mt-1">{{ $purchasableProducts->count() }}</h3>
          </div>
          <div class="bg-dark-subtle text-dark p-2 rounded">
            <i class="bi bi-boxes fs-4"></i>
          </div>
        </div>
        <div class="small text-muted mt-2">Raw Materials & Retail Goods</div>
      </div>
    </div>
  </div>
</div>

<!-- Tabs for Purchases and Purchasable Items Catalog -->
<ul class="nav nav-pills mb-3" id="purchaseTabs" role="tablist">
  <li class="nav-item" role="presentation">
    <button class="nav-link active fw-bold" id="purchases-tab" data-bs-toggle="pill" data-bs-target="#purchases-content" type="button" role="tab" aria-selected="true">
      <i class="bi bi-receipt-cutoff me-1"></i> Purchase Orders & Bills ({{ $purchases->total() }})
    </button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link fw-bold" id="products-tab" data-bs-toggle="pill" data-bs-target="#products-content" type="button" role="tab" aria-selected="false">
      <i class="bi bi-boxes me-1"></i> Purchasable Products & Materials ({{ $purchasableProducts->count() }})
    </button>
  </li>
</ul>

<div class="tab-content" id="purchaseTabsContent">
  <!-- TAB 1: PURCHASES LIST -->
  <div class="tab-pane fade show active" id="purchases-content" role="tabpanel" aria-labelledby="purchases-tab">
    <!-- Filter & Search Toolbar -->
    <div class="card border shadow-sm mb-3">
      <div class="card-body py-3">
        <form method="GET" action="{{ route('inventory.purchases') }}" class="row g-2 align-items-end">
          <div class="col-md-3">
            <label class="form-label small fw-semibold mb-1">Search Purchase / Supplier / Item</label>
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Search PO #, supplier, invoice..." value="{{ request('search') }}">
          </div>
          <div class="col-md-2">
            <label class="form-label small fw-semibold mb-1">Category</label>
            <select name="category_id" class="form-select form-select-sm">
              <option value="">All Categories</option>
              @foreach ($categories as $cat)
                <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label small fw-semibold mb-1">From Date</label>
            <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
          </div>
          <div class="col-md-2">
            <label class="form-label small fw-semibold mb-1">To Date</label>
            <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
          </div>
          <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-sm flex-fill">
              <i class="bi bi-filter me-1"></i> Apply Filter
            </button>
            <a href="{{ route('inventory.purchases') }}" class="btn btn-outline-secondary btn-sm" title="Reset Filters">
              <i class="bi bi-x-circle"></i>
            </a>
          </div>
        </form>
      </div>
    </div>

    <!-- Purchases Table -->
    <div class="card border shadow-sm">
      <div class="card-body p-0">
        <div class="table-responsive" style="min-height: 280px;">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>PO Number</th>
                <th>Date & Invoice</th>
                <th>Supplier Name</th>
                <th>Purchased Items (Category, UOM & Qty)</th>
                <th class="text-center">Total Qty</th>
                <th class="text-end">Grand Total</th>
                <th class="text-center">Status</th>
                <th class="text-end pe-3">Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($purchases as $po)
                <tr>
                  <td class="fw-bold text-primary">
                    <a href="javascript:void(0)" onclick="viewPurchaseDetails({{ $po->id }})" class="text-decoration-none">
                      {{ $po->purchase_number }}
                    </a>
                  </td>
                  <td>
                    <div class="fw-semibold">{{ $po->purchase_date->format('d M Y') }}</div>
                    @if ($po->invoice_number)
                      <div class="small text-muted">Bill #: <span class="fw-medium text-dark">{{ $po->invoice_number }}</span></div>
                    @endif
                  </td>
                  <td>
                    <div class="fw-semibold text-heading">{{ $po->supplier_name }}</div>
                    <div class="small text-muted">Paid via: {{ ucfirst($po->payment_method ?? 'cash') }}</div>
                  </td>
                  <td>
                    <div class="d-flex flex-column gap-1">
                      @foreach ($po->items->take(3) as $pItem)
                        <div class="small d-flex align-items-center gap-1">
                          <span class="badge bg-secondary-subtle text-dark border" style="font-size: 0.7rem;">
                            {{ $pItem->product?->category?->name ?? 'General' }}
                          </span>
                          <span class="badge bg-info-subtle text-info border" style="font-size: 0.7rem;">
                            {{ $pItem->product?->unit?->code ?? $pItem->product?->unit?->name ?? 'Units' }}
                          </span>
                          <strong>{{ rtrim(rtrim(number_format($pItem->quantity, 3), '0'), '.') }}</strong>
                          <span>{{ $pItem->product?->name }}</span>
                          <span class="text-muted">@ Rs. {{ number_format($pItem->unit_cost, 2) }}</span>
                        </div>
                      @endforeach
                      @if ($po->items->count() > 3)
                        <div class="small text-muted fst-italic">+ {{ $po->items->count() - 3 }} more item(s)...</div>
                      @endif
                    </div>
                  </td>
                  <td class="text-center fw-bold">
                    {{ rtrim(rtrim(number_format($po->totalQuantity(), 3), '0'), '.') }}
                  </td>
                  <td class="text-end fw-bold fs-6 text-primary">
                    Rs. {{ number_format($po->grand_total, 2) }}
                  </td>
                  <td class="text-center">
                    <span class="badge bg-success-subtle text-success border">
                      <i class="bi bi-check-circle me-1"></i>Received
                    </span>
                  </td>
                  <td class="text-end pe-3">
                    <div class="dropdown d-inline-block">
                      <button class="btn btn-sm btn-outline-secondary dropdown-toggle px-2 py-1" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-gear me-1"></i> Actions
                      </button>
                      <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li>
                          <a class="dropdown-item small" href="javascript:void(0)" onclick="viewPurchaseDetails({{ $po->id }})">
                            <i class="bi bi-eye text-primary me-2"></i> View Purchase Bill
                          </a>
                        </li>
                        <li>
                          <a class="dropdown-item small" href="javascript:void(0)" onclick="editPurchase({{ $po->id }})">
                            <i class="bi bi-pencil text-warning me-2"></i> Edit Purchase
                          </a>
                        </li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li>
                          <form method="POST" action="{{ route('inventory.purchases.destroy', $po->id) }}" onsubmit="return confirm('Are you sure you want to delete Purchase #{{ $po->purchase_number }}? This will reverse and subtract the purchased quantities from your current inventory.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="dropdown-item small text-danger">
                              <i class="bi bi-trash me-2"></i> Delete & Reverse Stock
                            </button>
                          </form>
                        </li>
                      </ul>
                    </div>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="8" class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-2 d-block mb-2 text-secondary"></i>
                    No purchases found matching your query. Click <strong>"New Purchase Bill"</strong> to record incoming stock.
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
      @if ($purchases->hasPages())
        <div class="card-footer bg-transparent py-2">
          {{ $purchases->links() }}
        </div>
      @endif
    </div>
  </div>

  <!-- TAB 2: PURCHASABLE PRODUCTS & RAW MATERIALS CATALOG -->
  <div class="tab-pane fade" id="products-content" role="tabpanel" aria-labelledby="products-tab">
    <div class="card border shadow-sm">
      <div class="card-header bg-transparent py-3 d-flex align-items-center justify-content-between">
        <div>
          <h5 class="fw-bold mb-0">Purchasable Products & Raw Materials Master</h5>
          <div class="small text-muted">All raw materials, ingredients, and retail goods eligible for procurement</div>
        </div>
        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#quickProductModal">
          <i class="bi bi-plus-circle me-1"></i> Add Material / Item
        </button>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Code / SKU</th>
                <th>Material / Product Name</th>
                <th>Classification</th>
                <th>Category</th>
                <th class="text-center">Unit of Measure (UOM)</th>
                <th class="text-end">Current Stock (Qty)</th>
                <th class="text-end">Unit Cost (Rs.)</th>
                <th class="text-end pe-3">Quick Purchase</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($purchasableProducts as $prod)
                <tr>
                  <td class="font-monospace fw-bold">{{ $prod->code }}</td>
                  <td>
                    <div class="fw-bold text-heading">{{ $prod->name }}</div>
                    @if ($prod->name_ur)
                      <div class="small text-muted" dir="rtl" style="font-family: 'Noto Nastaliq Urdu', Tahoma, sans-serif;">{{ $prod->name_ur }}</div>
                    @endif
                  </td>
                  <td>
                    @if ($prod->type === 'raw_material')
                      <span class="badge bg-warning text-dark"><i class="bi bi-egg me-1"></i>Raw Material</span>
                    @else
                      <span class="badge bg-secondary"><i class="bi bi-box-seam me-1"></i>Retail Good</span>
                    @endif
                  </td>
                  <td>
                    <span class="badge bg-light text-dark border">{{ $prod->category?->name ?? 'General' }}</span>
                  </td>
                  <td class="text-center">
                    <span class="badge bg-primary-subtle text-primary border px-2 py-1">
                      {{ $prod->unit?->code ?? $prod->unit?->name ?? 'Units' }}
                    </span>
                  </td>
                  <td class="text-end">
                    <span class="badge {{ $prod->isLowStock() ? 'bg-danger' : 'bg-success' }} fs-6">
                      {{ rtrim(rtrim(number_format($prod->current_stock, 3), '0'), '.') }} {{ $prod->unit?->code }}
                    </span>
                  </td>
                  <td class="text-end fw-semibold">
                    Rs. {{ number_format($prod->cost_price, 2) }}
                  </td>
                  <td class="text-end pe-3">
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="startPurchaseForItem({{ $prod->id }})">
                      <i class="bi bi-cart-plus me-1"></i> Purchase
                    </button>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="8" class="text-center py-4 text-muted">No raw materials or retail products found.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ======================================================= -->
<!-- 1. MULTI-ITEM NEW PURCHASE MODAL                        -->
<!-- ======================================================= -->
<div class="modal fade" id="addPurchaseModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content">
      <form method="POST" action="{{ route('inventory.purchases.store') }}" id="newPurchaseForm">
        @csrf
        <div class="modal-header">
          <div>
            <h5 class="modal-title fw-bold mb-0">Record Purchase Order (Incoming Procurement)</h5>
            <div class="small text-muted">Add one or multiple items with explicit Category, UOM, and Quantity</div>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <!-- Header Details -->
          <div class="row g-3 mb-4 p-3 bg-light rounded border">
            <div class="col-md-3">
              <label class="form-label small fw-semibold">Supplier / Vendor Name <span class="text-danger">*</span></label>
              <input type="text" name="supplier_name" class="form-control form-control-sm" placeholder="e.g. Metro Wholesale, Poultry Farm" required>
            </div>
            <div class="col-md-3">
              <label class="form-label small fw-semibold">Supplier Bill / Invoice #</label>
              <input type="text" name="invoice_number" class="form-control form-control-sm" placeholder="e.g. INV-98765">
            </div>
            <div class="col-md-2">
              <label class="form-label small fw-semibold">Purchase Date <span class="text-danger">*</span></label>
              <input type="date" name="purchase_date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required>
            </div>
            <div class="col-md-2">
              <label class="form-label small fw-semibold">Payment Method</label>
              <select name="payment_method" class="form-select form-select-sm">
                <option value="cash">Cash</option>
                <option value="bank">Bank / Cheque</option>
                <option value="credit">Credit (Vendor Payable)</option>
              </select>
            </div>
            <div class="col-md-2">
              <label class="form-label small fw-semibold">Need New Item?</label>
              <button type="button" class="btn btn-outline-primary btn-sm w-100" onclick="openQuickProductModalFromPurchase()">
                <i class="bi bi-plus-circle me-1"></i> + New Material
              </button>
            </div>
          </div>

          <!-- Line Items Table -->
          <div class="d-flex align-items-center justify-content-between mb-2">
            <h6 class="fw-bold mb-0 text-heading">
              <i class="bi bi-list-check me-1 text-primary"></i> Purchase Line Items
            </h6>
            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addPurchaseRow()">
              <i class="bi bi-plus-lg me-1"></i> + Add Another Item
            </button>
          </div>

          <div class="table-responsive mb-3">
            <table class="table table-sm table-bordered align-middle mb-0" id="purchaseItemsTable">
              <thead class="table-light">
                <tr>
                  <th style="width: 35%;">Product / Raw Material <span class="text-danger">*</span></th>
                  <th style="width: 15%;">Category</th>
                  <th class="text-center" style="width: 12%;">UOM</th>
                  <th class="text-center" style="width: 13%;">Quantity <span class="text-danger">*</span></th>
                  <th class="text-end" style="width: 13%;">Unit Cost (Rs.) <span class="text-danger">*</span></th>
                  <th class="text-end" style="width: 15%;">Line Total (Rs.)</th>
                  <th style="width: 40px;"></th>
                </tr>
              </thead>
              <tbody id="purchaseItemsTableBody">
                <!-- Rows inserted dynamically -->
              </tbody>
              <tfoot class="table-light fw-bold">
                <tr>
                  <td colspan="5" class="text-end">Purchase Grand Total:</td>
                  <td class="text-end text-primary fs-6" id="newPurchaseGrandTotalDisplay">Rs. 0.00</td>
                  <td></td>
                </tr>
              </tfoot>
            </table>
          </div>

          <div>
            <label class="form-label small fw-semibold">Purchase Notes / Remarks</label>
            <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="Optional notes (e.g. Received at central kitchen, quality checked)"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm fw-bold px-4">
            <i class="bi bi-check2-circle me-1"></i> Receive & Post to Inventory
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ======================================================= -->
<!-- 2. EDIT PURCHASE MODAL                                  -->
<!-- ======================================================= -->
<div class="modal fade" id="editPurchaseModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content">
      <form method="POST" id="editPurchaseForm">
        @csrf
        @method('PUT')
        <div class="modal-header">
          <div>
            <h5 class="modal-title fw-bold mb-0" id="editPurchaseTitle">Edit Purchase Order</h5>
            <div class="small text-muted">Stock will be automatically adjusted based on changes</div>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <div class="row g-3 mb-4 p-3 bg-light rounded border">
            <div class="col-md-3">
              <label class="form-label small fw-semibold">Supplier Name <span class="text-danger">*</span></label>
              <input type="text" name="supplier_name" id="edit_supplier_name" class="form-control form-control-sm" required>
            </div>
            <div class="col-md-3">
              <label class="form-label small fw-semibold">Supplier Bill / Invoice #</label>
              <input type="text" name="invoice_number" id="edit_invoice_number" class="form-control form-control-sm">
            </div>
            <div class="col-md-3">
              <label class="form-label small fw-semibold">Purchase Date <span class="text-danger">*</span></label>
              <input type="date" name="purchase_date" id="edit_purchase_date" class="form-control form-control-sm" required>
            </div>
            <div class="col-md-3">
              <label class="form-label small fw-semibold">Payment Method</label>
              <select name="payment_method" id="edit_payment_method" class="form-select form-select-sm">
                <option value="cash">Cash</option>
                <option value="bank">Bank / Cheque</option>
                <option value="credit">Credit (Vendor Payable)</option>
              </select>
            </div>
          </div>

          <div class="d-flex align-items-center justify-content-between mb-2">
            <h6 class="fw-bold mb-0 text-heading">
              <i class="bi bi-list-check me-1 text-primary"></i> Line Items
            </h6>
            <button type="button" class="btn btn-sm btn-outline-primary" onclick="addEditPurchaseRow()">
              <i class="bi bi-plus-lg me-1"></i> + Add Another Item
            </button>
          </div>

          <div class="table-responsive mb-3">
            <table class="table table-sm table-bordered align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th style="width: 35%;">Product / Raw Material <span class="text-danger">*</span></th>
                  <th style="width: 15%;">Category</th>
                  <th class="text-center" style="width: 12%;">UOM</th>
                  <th class="text-center" style="width: 13%;">Quantity <span class="text-danger">*</span></th>
                  <th class="text-end" style="width: 13%;">Unit Cost (Rs.) <span class="text-danger">*</span></th>
                  <th class="text-end" style="width: 15%;">Line Total (Rs.)</th>
                  <th style="width: 40px;"></th>
                </tr>
              </thead>
              <tbody id="editPurchaseItemsTableBody">
                <!-- Rows populated via AJAX -->
              </tbody>
              <tfoot class="table-light fw-bold">
                <tr>
                  <td colspan="5" class="text-end">Updated Grand Total:</td>
                  <td class="text-end text-primary fs-6" id="editPurchaseGrandTotalDisplay">Rs. 0.00</td>
                  <td></td>
                </tr>
              </tfoot>
            </table>
          </div>

          <div>
            <label class="form-label small fw-semibold">Notes / Remarks</label>
            <textarea name="notes" id="edit_notes" class="form-control form-control-sm" rows="2"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm fw-bold px-4">
            <i class="bi bi-check2-circle me-1"></i> Save Changes & Reconcile Stock
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ======================================================= -->
<!-- 3. VIEW PURCHASE DETAILS MODAL                          -->
<!-- ======================================================= -->
<div class="modal fade" id="viewPurchaseModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-bold" id="viewPurchaseTitle">Purchase Order Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4" id="viewPurchaseBody">
        <div class="text-center py-4">
          <div class="spinner-border spinner-border-sm text-primary me-2"></div> Loading purchase bill...
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-warning btn-sm" onclick="editFromViewModal()">
          <i class="bi bi-pencil me-1"></i> Edit Purchase
        </button>
        <button type="button" class="btn btn-primary btn-sm" onclick="printViewPurchase()">
          <i class="bi bi-printer me-1"></i> Print Slip
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ======================================================= -->
<!-- 4. QUICK ADD PURCHASABLE PRODUCT MODAL                  -->
<!-- ======================================================= -->
<div class="modal fade" id="quickProductModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-md">
    <div class="modal-content">
      <form id="quickProductForm" onsubmit="handleQuickProductSubmit(event)">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title fw-bold">Add Purchasable Item / Material</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Item Name <span class="text-danger">*</span></label>
            <input type="text" name="name" id="quick_name" class="form-control" placeholder="e.g. Mozzarella Cheese Block, Wheat Flour" required>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Item Name in Urdu (اردو نام)</label>
            <input type="text" name="name_ur" id="quick_name_ur" class="form-control" dir="rtl" placeholder="مثلاً: میدہ، پنیر">
          </div>
          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label small fw-semibold">Classification <span class="text-danger">*</span></label>
              <select name="type" id="quick_type" class="form-select" required>
                <option value="raw_material" selected>Raw Material / Ingredient</option>
                <option value="standard">Retail / Standard Good</option>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold">Category <span class="text-danger">*</span></label>
              <select name="category_id" id="quick_category_id" class="form-select" required>
                <option value="">-- Select Category --</option>
                @foreach ($categories as $cat)
                  <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label small fw-semibold">Unit of Measure (UOM) <span class="text-danger">*</span></label>
              <select name="unit_id" id="quick_unit_id" class="form-select" required>
                <option value="">-- Select UOM --</option>
                @foreach ($units as $u)
                  <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->code }})</option>
                @endforeach
              </select>
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold">Default Cost Price (Rs.)</label>
              <input type="number" name="cost_price" id="quick_cost_price" class="form-control" step="0.01" min="0" placeholder="0.00">
            </div>
          </div>
          <div class="row g-2 mb-2">
            <div class="col-6">
              <label class="form-label small fw-semibold">Initial Opening Qty</label>
              <input type="number" name="opening_stock" id="quick_opening_stock" class="form-control" step="0.001" min="0" value="0">
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold">Low Stock Alert Qty</label>
              <input type="number" name="min_stock" id="quick_min_stock" class="form-control" step="0.001" min="0" value="5">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm fw-bold" id="quickProductSubmitBtn">
            <i class="bi bi-check-circle me-1"></i> Save Material
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

@push('scripts')
<script>
// Global array of purchasable products with category & UOM
let purchasableProductsData = @json($purchasableProducts);
let newRowCounter = 0;
let editRowCounter = 0;

document.addEventListener('DOMContentLoaded', function() {
  const addModalEl = document.getElementById('addPurchaseModal');
  if (addModalEl) {
    addModalEl.addEventListener('show.bs.modal', function() {
      const tbody = document.getElementById('purchaseItemsTableBody');
      if (tbody && tbody.children.length === 0) {
        addPurchaseRow();
      }
    });
  }
  // Add initial row to new purchase form
  addPurchaseRow();
});

function getProductOptionsHtml(selectedId = null) {
  let html = '<option value="">-- Select Product / Item --</option>';
  purchasableProductsData.forEach(p => {
    const isSel = (selectedId && selectedId == p.id) ? 'selected' : '';
    const uom = (p.unit && p.unit.code) ? p.unit.code : (p.unit ? p.unit.name : 'Units');
    const cat = p.category ? p.category.name : 'General';
    const typeLabel = p.type === 'menu_item' ? 'Menu Item' : (p.type === 'raw_material' ? 'Raw Material' : 'Retail Good');
    html += `<option value="${p.id}" ${isSel} data-category="${cat}" data-unit="${uom}" data-cost="${p.cost_price || 0}">
      ${p.name} [${cat} • ${uom} • ${typeLabel}]
    </option>`;
  });
  return html;
}

// -------------------------------------------------------------
// NEW PURCHASE MODAL FUNCTIONS
// -------------------------------------------------------------
function addPurchaseRow(preselectedId = null, prefillQty = 1, prefillCost = null) {
  const tbody = document.getElementById('purchaseItemsTableBody');
  const rowId = `purchase_row_${newRowCounter++}`;
  const tr = document.createElement('tr');
  tr.id = rowId;

  tr.innerHTML = `
    <td>
      <select name="items[${newRowCounter}][product_id]" class="form-select form-select-sm product-select" onchange="onProductSelected(this, '${rowId}')" required>
        ${getProductOptionsHtml(preselectedId)}
      </select>
    </td>
    <td>
      <span class="badge bg-secondary-subtle text-dark border category-badge">Select item</span>
    </td>
    <td class="text-center">
      <span class="badge bg-info-subtle text-info border uom-badge">-</span>
    </td>
    <td>
      <input type="number" name="items[${newRowCounter}][quantity]" class="form-control form-control-sm text-center qty-input" value="${prefillQty}" step="0.001" min="0.001" oninput="calculateRowTotal('${rowId}')" required>
    </td>
    <td>
      <input type="number" name="items[${newRowCounter}][unit_cost]" class="form-control form-control-sm text-end cost-input" value="${prefillCost !== null ? prefillCost : '0.00'}" step="0.01" min="0" oninput="calculateRowTotal('${rowId}')" required>
    </td>
    <td class="text-end fw-semibold line-total">
      Rs. 0.00
    </td>
    <td class="text-center">
      <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="removePurchaseRow('${rowId}')">
        <i class="bi bi-trash"></i>
      </button>
    </td>
  `;

  tbody.appendChild(tr);

  // Trigger change to update category and unit badges
  const selectEl = tr.querySelector('.product-select');
  if (preselectedId) {
    onProductSelected(selectEl, rowId);
  }
}

function onProductSelected(selectEl, rowId) {
  const tr = document.getElementById(rowId);
  if (!tr) return;

  const opt = selectEl.options[selectEl.selectedIndex];
  const catBadge = tr.querySelector('.category-badge');
  const uomBadge = tr.querySelector('.uom-badge');
  const costInput = tr.querySelector('.cost-input');

  if (opt && opt.value) {
    catBadge.textContent = opt.getAttribute('data-category') || 'General';
    uomBadge.textContent = opt.getAttribute('data-unit') || 'Units';
    if (parseFloat(costInput.value) <= 0) {
      costInput.value = parseFloat(opt.getAttribute('data-cost') || 0).toFixed(2);
    }
  } else {
    catBadge.textContent = '-';
    uomBadge.textContent = '-';
  }

  calculateRowTotal(rowId);
}

function calculateRowTotal(rowId) {
  const tr = document.getElementById(rowId);
  if (!tr) return;

  const qty = parseFloat(tr.querySelector('.qty-input')?.value) || 0;
  const cost = parseFloat(tr.querySelector('.cost-input')?.value) || 0;
  const lineTotal = qty * cost;

  const totalEl = tr.querySelector('.line-total');
  if (totalEl) {
    totalEl.textContent = 'Rs. ' + lineTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  updateNewPurchaseGrandTotal();
}

function removePurchaseRow(rowId) {
  const tbody = document.getElementById('purchaseItemsTableBody');
  if (tbody.children.length <= 1) {
    alert('A purchase order must contain at least one item.');
    return;
  }
  document.getElementById(rowId)?.remove();
  updateNewPurchaseGrandTotal();
}

function updateNewPurchaseGrandTotal() {
  let grandTotal = 0;
  document.querySelectorAll('#purchaseItemsTableBody tr').forEach(tr => {
    const qty = parseFloat(tr.querySelector('.qty-input')?.value) || 0;
    const cost = parseFloat(tr.querySelector('.cost-input')?.value) || 0;
    grandTotal += (qty * cost);
  });

  document.getElementById('newPurchaseGrandTotalDisplay').textContent =
    'Rs. ' + grandTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function startPurchaseForItem(productId) {
  const modalEl = document.getElementById('addPurchaseModal');
  const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);

  // Switch to purchases tab
  document.getElementById('purchases-tab').click();

  // Clear rows and add single row with this product
  document.getElementById('purchaseItemsTableBody').innerHTML = '';
  newRowCounter = 0;
  addPurchaseRow(productId, 10);

  modal.show();
}

function openQuickProductModalFromPurchase() {
  const modalEl = document.getElementById('quickProductModal');
  const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
  modal.show();
}

// -------------------------------------------------------------
// EDIT PURCHASE MODAL FUNCTIONS
// -------------------------------------------------------------
async function editPurchase(id) {
  try {
    const res = await fetch(`/inventory/purchases/${id}/edit-data`);
    if (!res.ok) throw new Error('Failed to load purchase data');
    const po = await res.json();

    const form = document.getElementById('editPurchaseForm');
    form.action = `/inventory/purchases/${po.id}`;
    document.getElementById('editPurchaseTitle').textContent = `Edit Purchase Order #${po.purchase_number}`;
    document.getElementById('edit_supplier_name').value = po.supplier_name || '';
    document.getElementById('edit_invoice_number').value = po.invoice_number || '';
    document.getElementById('edit_purchase_date').value = po.purchase_date || '';
    document.getElementById('edit_payment_method').value = po.payment_method || 'cash';
    document.getElementById('edit_notes').value = po.notes || '';

    const tbody = document.getElementById('editPurchaseItemsTableBody');
    tbody.innerHTML = '';
    editRowCounter = 0;

    if (po.items && po.items.length > 0) {
      po.items.forEach(item => {
        addEditPurchaseRow(item.product_id, item.quantity, item.unit_cost);
      });
    } else {
      addEditPurchaseRow();
    }

    const modalEl = document.getElementById('editPurchaseModal');
    const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
    modal.show();
  } catch (err) {
    alert('Error loading purchase details: ' + err.message);
  }
}

function addEditPurchaseRow(preselectedId = null, prefillQty = 1, prefillCost = 0) {
  const tbody = document.getElementById('editPurchaseItemsTableBody');
  const rowId = `edit_row_${editRowCounter++}`;
  const tr = document.createElement('tr');
  tr.id = rowId;

  tr.innerHTML = `
    <td>
      <select name="items[${editRowCounter}][product_id]" class="form-select form-select-sm product-select" onchange="onEditProductSelected(this, '${rowId}')" required>
        ${getProductOptionsHtml(preselectedId)}
      </select>
    </td>
    <td>
      <span class="badge bg-secondary-subtle text-dark border category-badge">-</span>
    </td>
    <td class="text-center">
      <span class="badge bg-info-subtle text-info border uom-badge">-</span>
    </td>
    <td>
      <input type="number" name="items[${editRowCounter}][quantity]" class="form-control form-control-sm text-center qty-input" value="${prefillQty}" step="0.001" min="0.001" oninput="calculateEditRowTotal('${rowId}')" required>
    </td>
    <td>
      <input type="number" name="items[${editRowCounter}][unit_cost]" class="form-control form-control-sm text-end cost-input" value="${prefillCost}" step="0.01" min="0" oninput="calculateEditRowTotal('${rowId}')" required>
    </td>
    <td class="text-end fw-semibold line-total">
      Rs. 0.00
    </td>
    <td class="text-center">
      <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="removeEditPurchaseRow('${rowId}')">
        <i class="bi bi-trash"></i>
      </button>
    </td>
  `;

  tbody.appendChild(tr);

  const selectEl = tr.querySelector('.product-select');
  if (preselectedId) {
    onEditProductSelected(selectEl, rowId);
  }
}

function onEditProductSelected(selectEl, rowId) {
  const tr = document.getElementById(rowId);
  if (!tr) return;

  const opt = selectEl.options[selectEl.selectedIndex];
  const catBadge = tr.querySelector('.category-badge');
  const uomBadge = tr.querySelector('.uom-badge');
  const costInput = tr.querySelector('.cost-input');

  if (opt && opt.value) {
    catBadge.textContent = opt.getAttribute('data-category') || 'General';
    uomBadge.textContent = opt.getAttribute('data-unit') || 'Units';
    if (parseFloat(costInput.value) <= 0) {
      costInput.value = parseFloat(opt.getAttribute('data-cost') || 0).toFixed(2);
    }
  } else {
    catBadge.textContent = '-';
    uomBadge.textContent = '-';
  }

  calculateEditRowTotal(rowId);
}

function calculateEditRowTotal(rowId) {
  const tr = document.getElementById(rowId);
  if (!tr) return;

  const qty = parseFloat(tr.querySelector('.qty-input')?.value) || 0;
  const cost = parseFloat(tr.querySelector('.cost-input')?.value) || 0;
  const lineTotal = qty * cost;

  const totalEl = tr.querySelector('.line-total');
  if (totalEl) {
    totalEl.textContent = 'Rs. ' + lineTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  updateEditPurchaseGrandTotal();
}

function removeEditPurchaseRow(rowId) {
  const tbody = document.getElementById('editPurchaseItemsTableBody');
  if (tbody.children.length <= 1) {
    alert('A purchase order must contain at least one item.');
    return;
  }
  document.getElementById(rowId)?.remove();
  updateEditPurchaseGrandTotal();
}

function updateEditPurchaseGrandTotal() {
  let grandTotal = 0;
  document.querySelectorAll('#editPurchaseItemsTableBody tr').forEach(tr => {
    const qty = parseFloat(tr.querySelector('.qty-input')?.value) || 0;
    const cost = parseFloat(tr.querySelector('.cost-input')?.value) || 0;
    grandTotal += (qty * cost);
  });

  document.getElementById('editPurchaseGrandTotalDisplay').textContent =
    'Rs. ' + grandTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// -------------------------------------------------------------
// VIEW PURCHASE DETAILS FUNCTIONS
// -------------------------------------------------------------
let currentViewingPurchaseId = null;

async function viewPurchaseDetails(id) {
  currentViewingPurchaseId = id;
  const modalEl = document.getElementById('viewPurchaseModal');
  const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
  const body = document.getElementById('viewPurchaseBody');

  body.innerHTML = '<div class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary me-2"></div> Loading purchase details...</div>';
  modal.show();

  try {
    const res = await fetch(`/inventory/purchases/${id}`);
    if (!res.ok) throw new Error('Failed to load purchase');
    const po = await res.json();

    document.getElementById('viewPurchaseTitle').innerHTML = `Purchase Order <strong>#${po.purchase_number}</strong>`;

    let itemsHtml = '';
    po.items.forEach((item, idx) => {
      itemsHtml += `
        <tr>
          <td>${idx + 1}</td>
          <td>
            <div class="fw-bold">${item.product_name}</div>
            <div class="small text-muted">Code: ${item.product_code}</div>
          </td>
          <td><span class="badge bg-secondary-subtle text-dark border">${item.category_name}</span></td>
          <td class="text-center"><span class="badge bg-info-subtle text-info border">${item.unit_code}</span></td>
          <td class="text-center fw-bold">${item.quantity}</td>
          <td class="text-end">Rs. ${item.unit_cost.toFixed(2)}</td>
          <td class="text-end fw-bold">Rs. ${item.subtotal.toFixed(2)}</td>
        </tr>
      `;
    });

    body.innerHTML = `
      <div id="printablePurchaseBill">
        <div class="d-flex justify-content-between align-items-start border-bottom pb-3 mb-3">
          <div>
            <h5 class="fw-bold text-primary mb-1">PURCHASE ORDER</h5>
            <div class="fs-6 fw-bold">${po.purchase_number}</div>
            <div class="small text-muted">Date: ${po.purchase_date_formatted}</div>
          </div>
          <div class="text-end">
            <h6 class="fw-bold mb-0">${po.supplier_name}</h6>
            ${po.invoice_number ? `<div class="small text-muted">Supplier Bill #: <strong>${po.invoice_number}</strong></div>` : ''}
            <div class="small text-muted">Payment: <strong>${po.payment_method}</strong></div>
          </div>
        </div>

        <div class="table-responsive mb-3">
          <table class="table table-sm table-bordered align-middle">
            <thead class="table-light">
              <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 35%;">Product / Material</th>
                <th style="width: 15%;">Category</th>
                <th class="text-center" style="width: 10%;">UOM</th>
                <th class="text-center" style="width: 10%;">Qty</th>
                <th class="text-end" style="width: 12%;">Unit Cost</th>
                <th class="text-end" style="width: 13%;">Total</th>
              </tr>
            </thead>
            <tbody>
              ${itemsHtml}
            </tbody>
            <tfoot class="table-light">
              <tr>
                <td colspan="6" class="text-end fw-bold">Grand Total:</td>
                <td class="text-end fw-bold text-primary fs-6">Rs. ${po.grand_total.toFixed(2)}</td>
              </tr>
            </tfoot>
          </table>
        </div>

        <div class="row small text-muted">
          <div class="col-6">
            <div>Recorded by: <strong>${po.recorded_by}</strong> (${po.created_at})</div>
          </div>
          <div class="col-6 text-end">
            ${po.notes ? `<div>Remarks: <em>${po.notes}</em></div>` : ''}
          </div>
        </div>
      </div>
    `;
  } catch (err) {
    body.innerHTML = `<div class="alert alert-danger mb-0">Error loading details: ${err.message}</div>`;
  }
}

function editFromViewModal() {
  if (!currentViewingPurchaseId) return;
  const viewModalEl = document.getElementById('viewPurchaseModal');
  const modal = bootstrap.Modal.getInstance(viewModalEl);
  if (modal) modal.hide();
  editPurchase(currentViewingPurchaseId);
}

function printViewPurchase() {
  const content = document.getElementById('printablePurchaseBill')?.innerHTML;
  if (!content) return;

  let printFrame = document.getElementById('purchasePrintFrame');
  if (printFrame) {
    printFrame.remove();
  }

  printFrame = document.createElement('iframe');
  printFrame.id = 'purchasePrintFrame';
  printFrame.style.position = 'fixed';
  printFrame.style.right = '0';
  printFrame.style.bottom = '0';
  printFrame.style.width = '0';
  printFrame.style.height = '0';
  printFrame.style.border = '0';
  document.body.appendChild(printFrame);

  const doc = printFrame.contentWindow.document;
  doc.open();
  doc.write('<!DOCTYPE html>');
  doc.write('<html>');
  doc.write('<h' + 'ead>');
  doc.write('<title>Purchase Order Bill</title>');
  doc.write('<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">');
  doc.write('<style>body { padding: 30px; font-family: system-ui, -apple-system, sans-serif; }</style>');
  doc.write('</h' + 'ead>');
  doc.write('<b' + 'ody>');
  doc.write(content);
  doc.write('</b' + 'ody>');
  doc.write('</html>');
  doc.close();

  setTimeout(() => {
    try {
      printFrame.contentWindow.focus();
      printFrame.contentWindow.print();
    } catch (e) {
      console.error('Print error:', e);
    }
    setTimeout(() => {
      printFrame.remove();
    }, 1500);
  }, 350);
}

// -------------------------------------------------------------
// QUICK ADD PRODUCT FORM SUBMIT
// -------------------------------------------------------------
async function handleQuickProductSubmit(e) {
  e.preventDefault();
  const form = document.getElementById('quickProductForm');
  const submitBtn = document.getElementById('quickProductSubmitBtn');
  const formData = new FormData(form);

  submitBtn.disabled = true;
  submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';

  try {
    const res = await fetch("{{ route('inventory.purchases.products.store') }}", {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
      },
      body: formData,
    });

    const data = await res.json();
    if (!res.ok || !data.success) {
      throw new Error(data.message || 'Failed to save product');
    }

    // Add newly created product to global array
    purchasableProductsData.push(data.product);

    // Close modal & reset form
    const modalEl = document.getElementById('quickProductModal');
    const modal = bootstrap.Modal.getInstance(modalEl);
    if (modal) modal.hide();
    form.reset();

    // Re-populate active select dropdowns with the new option
    document.querySelectorAll('.product-select').forEach(select => {
      const currentVal = select.value;
      select.innerHTML = getProductOptionsHtml(currentVal);
    });

    // Add a new row pre-selecting this new product in new purchase modal
    addPurchaseRow(data.product.id, 1, data.product.cost_price);

    alert(`Item '${data.product.name}' created and added to your purchase list!`);
  } catch (err) {
    alert('Error creating item: ' + err.message);
  } finally {
    submitBtn.disabled = false;
    submitBtn.innerHTML = '<i class="bi bi-check-circle me-1"></i> Save Material';
  }
}
</script>
@endpush
@endsection
