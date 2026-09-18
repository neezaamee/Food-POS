@extends('layouts.app', ['title' => 'Products & Menu Items - Food Point POS'])

@section('content')
<div class="page-header d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
  <div>
    <h1 class="page-title">Products & Food Catalog</h1>
    <nav class="breadcrumb small">
      <a href="{{ route('dashboard') }}" class="breadcrumb-item text-decoration-none">Home</a>
      <span class="breadcrumb-item active">Products & Recipes</span>
    </nav>
  </div>
  <div class="d-flex gap-2">
    <button type="button" class="btn btn-primary btn-sm px-3 py-2" data-bs-toggle="modal" data-bs-target="#addProductModal">
      <i class="bi bi-plus-circle me-1"></i> Add New Item
    </button>
  </div>
</div>

<!-- Type Filter Tabs -->
<ul class="nav nav-pills mb-3 gap-2">
  <li class="nav-item">
    <a class="nav-link {{ !request('type') ? 'active' : '' }}" href="{{ route('resources.products.index', array_merge(request()->except('type', 'page'))) }}">
      All Catalog Items
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link {{ request('type') === 'menu_item' ? 'active' : '' }}" href="{{ route('resources.products.index', array_merge(request()->except('type', 'page'), ['type' => 'menu_item'])) }}">
      <i class="bi bi-cup-hot me-1"></i> Menu Dishes (BOM / Recipe)
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link {{ request('type') === 'raw_material' ? 'active' : '' }}" href="{{ route('resources.products.index', array_merge(request()->except('type', 'page'), ['type' => 'raw_material'])) }}">
      <i class="bi bi-egg me-1"></i> Raw Materials & Ingredients
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link {{ request('type') === 'standard' ? 'active' : '' }}" href="{{ route('resources.products.index', array_merge(request()->except('type', 'page'), ['type' => 'standard'])) }}">
      <i class="bi bi-box-seam me-1"></i> Retail Goods
    </a>
  </li>
</ul>

<!-- Search & Category Filters -->
<div class="card border mb-4">
  <div class="card-body p-3">
    <form method="GET" action="{{ route('resources.products.index') }}" class="row g-2 align-items-center">
      @if(request('type'))
        <input type="hidden" name="type" value="{{ request('type') }}">
      @endif
      <div class="col-md-5">
        <div class="input-group input-group-sm">
          <span class="input-group-text bg-surface"><i class="bi bi-search"></i></span>
          <input type="text" name="search" class="form-control" placeholder="Search by name, SKU, code, barcode..." value="{{ request('search') }}">
        </div>
      </div>
      <div class="col-md-4">
        <select name="category" class="form-select form-select-sm">
          <option value="">-- All Categories --</option>
          @foreach ($categories as $cat)
            <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-3 d-flex gap-2">
        <button type="submit" class="btn btn-sm btn-primary flex-grow-1">Filter</button>
        <a href="{{ route('resources.products.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
      </div>
    </form>
  </div>
</div>

<div class="card border">
  <div class="card-body p-0">
    <div class="table-responsive" style="min-height: 280px;">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Product Name</th>
            <th>Type</th>
            <th>Category</th>
            <th>Cost Price / Recipe</th>
            <th>Sale Price</th>
            <th>Profit Margin</th>
            <th>Stock</th>
            <th class="text-end pe-3">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($products as $prod)
            <tr id="prod-row-{{ $prod->id }}">
              <td>
                <div class="d-flex align-items-center gap-2.5">
                  @if ($prod->image_url)
                    <img src="{{ $prod->image_url }}" alt="{{ $prod->name }}" class="rounded border object-fit-cover flex-shrink-0" style="width: 44px; height: 44px;">
                  @else
                    <div class="rounded border bg-light d-flex align-items-center justify-content-center text-muted flex-shrink-0" style="width: 44px; height: 44px;">
                      <i class="bi bi-image text-secondary opacity-50" style="font-size: 1.25rem;"></i>
                    </div>
                  @endif
                  <div class="min-w-0">
                    <div class="fw-bold text-heading d-flex align-items-center flex-wrap gap-1">
                      <span>{{ $prod->name }}</span>
                      @if ($prod->name_ur)
                        <span class="badge bg-secondary-subtle text-dark border ms-1" style="font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', Tahoma, sans-serif; font-size: 0.85rem; direction: rtl;">{{ $prod->name_ur }}</span>
                      @endif
                      @if ($prod->hasVariants())
                        <span class="badge bg-primary-subtle text-primary border" style="font-size: 0.7rem;">
                          <i class="bi bi-layers me-1"></i>{{ $prod->variants->count() }} Sizes
                        </span>
                      @endif
                    </div>
                    <div class="small text-muted">
                      <span class="badge bg-light text-dark border me-1">{{ $prod->code }}</span>
                      {{ $prod->unit?->name ?? 'PCS' }}
                      @if ($prod->hasVariants())
                        &bull; <span class="text-primary fw-medium">{{ $prod->variants->pluck('variation_name')->filter()->join(', ') }}</span>
                      @elseif ($prod->barcode)
                        &bull; <span class="font-monospace">{{ $prod->barcode }}</span>
                      @endif
                    </div>
                  </div>
                </div>
              </td>
              <td>
                @if ($prod->type === 'raw_material')
                  <span class="badge bg-warning text-dark"><i class="bi bi-egg me-1"></i>Raw Material</span>
                @elseif ($prod->type === 'menu_item')
                  <span class="badge bg-info text-dark"><i class="bi bi-cup-hot me-1"></i>Menu Dish</span>
                @else
                  <span class="badge bg-secondary"><i class="bi bi-box-seam me-1"></i>Retail</span>
                @endif
              </td>
              <td>{{ $prod->category?->name ?? 'General' }}</td>
              <td>
                @if ($prod->hasVariants())
                  <div class="fw-semibold text-heading small">
                    <i class="bi bi-layers text-primary me-1"></i>Multiple Sizes
                  </div>
                  @php
                    $recipesCount = $prod->variants->filter(fn($v) => $v->recipeItems->isNotEmpty())->count();
                  @endphp
                  @if ($recipesCount > 0)
                    <div class="small text-success" style="font-size: 0.75rem;">
                      <i class="bi bi-check-circle-fill me-1"></i>{{ $recipesCount }}/{{ $prod->variants->count() }} recipes active
                    </div>
                  @else
                    <div class="small text-warning" style="font-size: 0.72rem;">No recipes configured</div>
                  @endif
                @elseif ($prod->isMenuItem() && $prod->hasRecipe())
                  <div class="fw-semibold text-success">
                    Rs. {{ number_format($prod->calculateRecipeCost(), 2) }}
                  </div>
                  <div class="small text-muted" style="font-size: 0.75rem;">
                    <i class="bi bi-check-circle-fill text-success me-1"></i>{{ $prod->recipeItems->count() }} ingredients
                  </div>
                @else
                  <div class="small">Rs. {{ number_format($prod->cost_price, 2) }}</div>
                  @if ($prod->isMenuItem())
                    <div class="small text-warning" style="font-size: 0.72rem;">No recipe linked</div>
                  @endif
                @endif
              </td>
              <td class="fw-bold text-primary">
                @if ($prod->isRawMaterial())
                  <span class="text-muted small">N/A (Raw)</span>
                @elseif ($prod->hasVariants())
                  <span>{{ $prod->formattedPriceRange() }}</span>
                @else
                  Rs. {{ number_format($prod->sale_price, 2) }}
                @endif
              </td>
              <td>
                @if ($prod->hasVariants())
                  <span class="badge bg-light text-dark border">By Size</span>
                @elseif (!$prod->isRawMaterial() && $prod->sale_price > 0)
                  @php
                    $margin = $prod->calculateProfitMargin();
                  @endphp
                  <span class="badge {{ $margin >= 40 ? 'bg-success' : ($margin >= 20 ? 'bg-warning text-dark' : 'bg-danger') }}">
                    {{ number_format($margin, 1) }}%
                  </span>
                @else
                  <span class="text-muted small">-</span>
                @endif
              </td>
              <td>
                <span class="badge {{ $prod->isLowStock() ? 'bg-danger' : 'bg-success' }}">
                  {{ rtrim(rtrim(number_format($prod->current_stock, 3), '0'), '.') }} {{ $prod->unit?->code }}
                </span>
              </td>
              <td class="text-end pe-3">
                <script type="application/json" id="prod-data-{{ $prod->id }}">@json($prod->load('variants'))</script>
                <div class="dropdown d-inline-block">
                  <button class="btn btn-sm btn-outline-secondary dropdown-toggle px-2 py-1" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-gear me-1"></i> Actions
                  </button>
                  <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li>
                      <a class="dropdown-item small" href="javascript:void(0)" onclick="handleEditProduct({{ $prod->id }})">
                        <i class="bi bi-pencil text-primary me-2"></i> Edit Product
                      </a>
                    </li>
                    @if (!$prod->isRawMaterial())
                      <li>
                        <a class="dropdown-item small" href="javascript:void(0)" onclick="handleManageVariants({{ $prod->id }})">
                          <i class="bi bi-layers text-info me-2"></i> Sizes / Variations
                        </a>
                      </li>
                    @endif
                    @if ($prod->isMenuItem())
                      <li>
                        <a class="dropdown-item small" href="javascript:void(0)" onclick="openRecipeModal({{ $prod->id }})">
                          <i class="bi bi-receipt text-success me-2"></i> Recipe (BOM)
                        </a>
                      </li>
                    @endif
                    <li><hr class="dropdown-divider my-1"></li>
                    <li>
                      <form method="POST" action="{{ route('resources.products.destroy', $prod->id) }}" onsubmit="return confirm('Are you sure you want to delete product \'{{ addslashes($prod->name) }}\'?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="dropdown-item small text-danger">
                          <i class="bi bi-trash me-2"></i> Delete Product
                        </button>
                      </form>
                    </li>
                  </ul>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="8" class="text-center py-4 text-muted">No products found for selected filter.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
  @if ($products->hasPages())
    <div class="card-footer bg-transparent py-2">
      {{ $products->links() }}
    </div>
  @endif
</div>

<!-- ADD PRODUCT MODAL -->
<div class="modal fade" id="addProductModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <form method="POST" action="{{ route('resources.products.store') }}" enctype="multipart/form-data">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title fw-bold">Add Catalog Item</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <!-- Item Type Selector -->
          <div class="mb-3 p-3 bg-light rounded border">
            <label class="form-label small fw-bold text-uppercase text-muted d-block mb-2">Item Classification</label>
            <div class="d-flex flex-wrap gap-4">
              <div class="form-check">
                <input class="form-check-input" type="radio" name="type" id="typeMenuItem" value="menu_item" checked onchange="toggleTypeFields('menu_item')">
                <label class="form-check-label fw-semibold" for="typeMenuItem">
                  <i class="bi bi-cup-hot me-1 text-primary"></i> Prepared Menu Dish
                  <span class="d-block small text-muted fw-normal">Sold on POS; raw materials are deducted dynamically via recipe</span>
                </label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="type" id="typeRawMaterial" value="raw_material" onchange="toggleTypeFields('raw_material')">
                <label class="form-check-label fw-semibold" for="typeRawMaterial">
                  <i class="bi bi-egg me-1 text-warning"></i> Raw Material / Ingredient
                  <span class="d-block small text-muted fw-normal">Purchased on POs (e.g. Dough, Cheese, Meat); NOT shown directly on POS</span>
                </label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="type" id="typeStandard" value="standard" onchange="toggleTypeFields('standard')">
                <label class="form-check-label fw-semibold" for="typeStandard">
                  <i class="bi bi-box-seam me-1 text-secondary"></i> Retail / Standard Good
                  <span class="d-block small text-muted fw-normal">Purchased and sold as-is without recipe (e.g. Canned Drinks)</span>
                </label>
              </div>
            </div>
          </div>
          <!-- Has Variations Toggle -->
          <div class="mb-3 p-3 bg-light rounded border" id="hasVariantsBox">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" name="has_variants" id="hasVariantsToggle" value="1" onchange="toggleVariantSection(this.checked)">
              <label class="form-check-label fw-bold" for="hasVariantsToggle">
                <i class="bi bi-layers text-primary me-1"></i> Multiple Sizes / Variations (e.g. Small, Medium, Large)
              </label>
            </div>
            <div class="small text-muted mt-1">Enable if this dish or beverage has different sizes, volumes, or portions with distinct prices & recipes.</div>

            <div id="variantsInputsContainer" class="mt-3" style="display: none;">
              <div class="table-responsive">
                <table class="table table-sm table-bordered bg-white align-middle mb-2">
                  <thead class="table-light">
                    <tr>
                      <th style="width: 40%;">Size / Portion Name</th>
                      <th style="width: 30%;">Sale Price (Rs.)</th>
                      <th style="width: 25%;">Code / SKU (Optional)</th>
                      <th style="width: 35px;"></th>
                    </tr>
                  </thead>
                  <tbody id="variantsTableBody">
                    <!-- Dynamic rows inserted here -->
                  </tbody>
                </table>
              </div>
              <button type="button" class="btn btn-sm btn-outline-primary" onclick="addVariantRowInAddModal()">
                <i class="bi bi-plus-circle me-1"></i> Add Another Size
              </button>
            </div>
          </div>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Item Name (English) <span class="text-danger">*</span></label>
              <input type="text" name="name" class="form-control" placeholder="e.g. Chicken Supreme Pizza" required>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Item Name in Urdu (اردو نام)</label>
              <input type="text" name="name_ur" class="form-control" dir="rtl" placeholder="مثلاً: چکن سپریم پیزا" style="font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', sans-serif;">
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold">Product Code / SKU</label>
              <input type="text" name="code" class="form-control" placeholder="e.g. FP-PZ-01 or FP-SW-01" required>
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold">Category</label>
              <select name="category_id" class="form-select" required>
                @foreach ($categories as $cat)
                  <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold">Brand</label>
              <select name="brand_id" class="form-select">
                <option value="">-- None --</option>
                @foreach ($brands as $b)
                  <option value="{{ $b->id }}">{{ $b->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold">Measurement Unit</label>
              <select name="unit_id" class="form-select">
                @foreach ($units as $u)
                  <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->code }})</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold">Cost Price (Rs.)</label>
              <input type="number" name="cost_price" class="form-control" value="0.00" step="0.01" min="0">
              <div class="small text-muted" style="font-size: 0.72rem;">For menu items, this auto-updates when recipe is configured</div>
            </div>
            <div class="col-md-4" id="addSalePriceContainer">
              <label class="form-label small fw-semibold">Base / Starting Sale Price (Rs.)</label>
              <input type="number" name="sale_price" id="addSalePriceInput" class="form-control" value="0.00" step="0.01" min="0">
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold">Initial Stock (Qty)</label>
              <input type="number" name="opening_stock" class="form-control" value="0" step="0.001" min="0">
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Low Stock Alert Level</label>
              <input type="number" name="min_stock" class="form-control" value="5" step="0.001" min="0">
            </div>
            <div class="col-md-6" id="addPrepTimeContainer">
              <label class="form-label small fw-semibold">Preparation Time (Minutes)</label>
              <input type="number" name="prep_time_minutes" class="form-control" value="15" min="1">
            </div>
            <!-- Product Photo Upload -->
            <div class="col-12">
              <label class="form-label small fw-semibold">Product Photo / Image</label>
              <div class="d-flex align-items-center gap-3">
                <div id="add_image_preview_box" class="border rounded bg-light d-flex align-items-center justify-content-center overflow-hidden flex-shrink-0" style="width: 65px; height: 65px;">
                  <i class="bi bi-camera text-muted fs-3"></i>
                </div>
                <div class="flex-grow-1">
                  <input type="file" name="image" id="add_product_image" class="form-control form-control-sm" accept="image/*" onchange="previewProductImage(this, 'add_image_preview_box')">
                  <div class="form-text small text-muted">Upload an image for menu & POS card display (JPG, PNG, WebP, max 4MB).</div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Item</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- EDIT PRODUCT MODAL -->
<div class="modal fade" id="editProductModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <form method="POST" id="editProductForm" action="" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="modal-header">
          <h5 class="modal-title fw-bold">Edit Catalog Item</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Item Name (English) <span class="text-danger">*</span></label>
              <input type="text" name="name" id="edit_name" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Item Name in Urdu (اردو نام)</label>
              <input type="text" name="name_ur" id="edit_name_ur" class="form-control" dir="rtl" placeholder="مثلاً: چکن سپریم پیزا" style="font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', sans-serif;">
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold">Item Type</label>
              <select name="type" id="edit_type" class="form-select" required>
                <option value="menu_item">Menu Dish (BOM Recipe)</option>
                <option value="raw_material">Raw Material / Ingredient</option>
                <option value="standard">Retail / Standard Good</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold">Category</label>
              <select name="category_id" id="edit_category_id" class="form-select" required>
                @foreach ($categories as $cat)
                  <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold">Cost Price (Rs.)</label>
              <input type="number" name="cost_price" id="edit_cost_price" class="form-control" step="0.01" min="0" required>
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold">Sale Price (Rs.)</label>
              <input type="number" name="sale_price" id="edit_sale_price" class="form-control" step="0.01" min="0" required>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Low Stock Alert</label>
              <input type="number" name="min_stock" id="edit_min_stock" class="form-control" step="0.001" min="0">
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Prep Time (Mins)</label>
              <input type="number" name="prep_time_minutes" id="edit_prep_time_minutes" class="form-control" min="1">
            </div>
            <!-- Edit Product Photo -->
            <div class="col-12">
              <label class="form-label small fw-semibold">Product Photo / Image</label>
              <div class="d-flex align-items-center gap-3">
                <div id="edit_image_preview_box" class="border rounded bg-light d-flex align-items-center justify-content-center overflow-hidden flex-shrink-0" style="width: 65px; height: 65px;">
                  <i class="bi bi-camera text-muted fs-3"></i>
                </div>
                <div class="flex-grow-1">
                  <input type="file" name="image" id="edit_product_image" class="form-control form-control-sm" accept="image/*" onchange="previewProductImage(this, 'edit_image_preview_box')">
                  <div class="form-check mt-1" id="edit_remove_image_wrapper" style="display: none;">
                    <input class="form-check-input" type="checkbox" name="remove_image" id="edit_remove_image" value="1">
                    <label class="form-check-label small text-danger" for="edit_remove_image">Remove current photo</label>
                  </div>
                  <div class="form-text small text-muted">Upload a new photo to change the current one.</div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Update Item</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- MANAGE SIZES / VARIANTS MODAL -->
<div class="modal fade" id="manageVariantsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <form method="POST" id="manageVariantsForm" action="">
        @csrf
        <div class="modal-header bg-light">
          <div>
            <h5 class="modal-title fw-bold" id="manageVariantsTitle"><i class="bi bi-layers text-primary me-2"></i>Manage Sizes & Variations</h5>
            <div class="small text-muted" id="manageVariantsSubtitle">Configure sizes (e.g. Small, Medium, Large) and their respective selling prices</div>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <div class="alert alert-info py-2 px-3 small d-flex align-items-center gap-2 mb-3">
            <i class="bi bi-info-circle-fill fs-5 flex-shrink-0"></i>
            <div>Each size variation acts as a distinct sellable option with its own <strong>Recipe (BOM)</strong> and food cost. The cashier will see one tile on POS and tap to select a size.</div>
          </div>

          <div class="table-responsive mb-3">
            <table class="table table-bordered align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th style="width: 40%;">Size / Variation Name</th>
                  <th style="width: 30%;">Sale Price (Rs.)</th>
                  <th style="width: 25%;">Code / SKU</th>
                  <th style="width: 50px;"></th>
                </tr>
              </thead>
              <tbody id="manageVariantsTableBody">
                <!-- Rows loaded dynamically -->
              </tbody>
            </table>
          </div>
          <button type="button" class="btn btn-sm btn-outline-primary" onclick="addVariantRowInManageModal()">
            <i class="bi bi-plus-circle me-1"></i> Add Another Size
          </button>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary px-4">
            <i class="bi bi-check-lg me-1"></i> Save Sizes & Variations
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- RECIPE / BILL OF MATERIALS (BOM) MODAL -->
<div class="modal fade" id="recipeModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content">
      <form method="POST" id="recipeForm" action="">
        @csrf
        <input type="hidden" name="target_product_id" id="recipeTargetProductId" value="">
        <div class="modal-header bg-light">
          <div>
            <h5 class="modal-title fw-bold" id="recipeModalTitle">Bill of Materials (BOM) & Recipe</h5>
            <div class="small text-muted" id="recipeModalSubtitle">Define raw materials and quantities deducted when this dish is sold</div>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-4">
          <!-- Size Variation Tabs (If Dish has multiple sizes) -->
          <div id="recipeVariantTabsWrapper" style="display: none;" class="mb-3 p-3 bg-light rounded border">
            <div class="small text-muted text-uppercase fw-bold mb-2">
              <i class="bi bi-layers text-primary me-1"></i> Select Size To View / Edit Recipe BOM:
            </div>
            <ul class="nav nav-pills gap-2" id="recipeVariantPills">
              <!-- Dynamically generated size pills -->
            </ul>
          </div>

          <!-- Summary Cards -->
          <div class="row g-3 mb-4">
            <div class="col-sm-6 col-md-3">
              <div class="card bg-surface border p-3">
                <div class="small text-muted text-uppercase fw-semibold">Selling Price</div>
                <div class="fs-4 fw-bold text-primary" id="modalSalePrice">Rs. 0.00</div>
              </div>
            </div>
            <div class="col-sm-6 col-md-3">
              <div class="card bg-surface border p-3">
                <div class="small text-muted text-uppercase fw-semibold">Recipe Food Cost</div>
                <div class="fs-4 fw-bold text-danger" id="modalRecipeCost">Rs. 0.00</div>
              </div>
            </div>
            <div class="col-sm-6 col-md-3">
              <div class="card bg-surface border p-3">
                <div class="small text-muted text-uppercase fw-semibold">Gross Profit / Dish</div>
                <div class="fs-4 fw-bold text-success" id="modalGrossProfit">Rs. 0.00</div>
              </div>
            </div>
            <div class="col-sm-6 col-md-3">
              <div class="card bg-surface border p-3">
                <div class="small text-muted text-uppercase fw-semibold">Profit Margin</div>
                <div class="fs-4 fw-bold text-info" id="modalMarginPct">0.0%</div>
              </div>
            </div>
          </div>

          <!-- Recipe Ingredients Table -->
          <div class="table-responsive mb-3">
            <table class="table table-bordered align-middle mb-0" id="recipeTable">
              <thead class="table-light">
                <tr>
                  <th style="width: 40%;">Raw Material / Ingredient</th>
                  <th style="width: 15%;">Unit</th>
                  <th style="width: 18%;">Required Qty</th>
                  <th style="width: 15%;">Unit Cost (Rs.)</th>
                  <th style="width: 17%;">Line Cost (Rs.)</th>
                  <th style="width: 50px;"></th>
                </tr>
              </thead>
              <tbody id="recipeRowsContainer">
                <!-- Dynamic Ingredient Rows Injected Here -->
              </tbody>
            </table>
          </div>

          <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <button type="button" class="btn btn-outline-primary btn-sm" onclick="addRecipeIngredientRow()">
              <i class="bi bi-plus-circle me-1"></i> Add Ingredient
            </button>
            <div class="small text-muted">
              <i class="bi bi-info-circle me-1"></i>
              Raw materials with zero cost will use their latest purchase price.
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary px-4">
            <i class="bi bi-check-lg me-1"></i> Save Recipe & Update Food Cost
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

@push('scripts')
<script>
const rawMaterialsList = @json($rawMaterials);
let currentDishSalePrice = 0.0;
let rowCounter = 0;
let addVariantCounter = 0;
let manageVariantCounter = 0;
let currentRecipeData = null;
let activeVariantId = null;

function toggleTypeFields(type) {
  const salePriceContainer = document.getElementById('addSalePriceContainer');
  const prepTimeContainer = document.getElementById('addPrepTimeContainer');
  const salePriceInput = document.getElementById('addSalePriceInput');
  const hasVariantsBox = document.getElementById('hasVariantsBox');

  if (type === 'raw_material') {
    if (salePriceInput) salePriceInput.value = '0.00';
    if (salePriceContainer) salePriceContainer.style.opacity = '0.4';
    if (prepTimeContainer) prepTimeContainer.style.opacity = '0.4';
    if (hasVariantsBox) hasVariantsBox.style.display = 'none';
  } else {
    if (salePriceContainer) salePriceContainer.style.opacity = '1';
    if (prepTimeContainer) prepTimeContainer.style.opacity = '1';
    if (hasVariantsBox) hasVariantsBox.style.display = 'block';
  }
}

function toggleVariantSection(checked) {
  const container = document.getElementById('variantsInputsContainer');
  const tableBody = document.getElementById('variantsTableBody');
  const salePriceContainer = document.getElementById('addSalePriceContainer');

  if (checked) {
    container.style.display = 'block';
    if (tableBody.children.length === 0) {
      addVariantRowInAddModal('Small', 650);
      addVariantRowInAddModal('Medium', 1050);
      addVariantRowInAddModal('Large', 1500);
    }
  } else {
    container.style.display = 'none';
  }
}

function addVariantRowInAddModal(name = '', price = '', code = '') {
  const tbody = document.getElementById('variantsTableBody');
  const rowId = `add_var_${addVariantCounter++}`;
  const tr = document.createElement('tr');
  tr.id = rowId;
  tr.innerHTML = `
    <td>
      <input type="text" name="variants[${addVariantCounter}][name]" class="form-control form-control-sm" value="${name}" placeholder="e.g. Small / 1.5L" required>
    </td>
    <td>
      <input type="number" name="variants[${addVariantCounter}][sale_price]" class="form-control form-control-sm" value="${price}" step="0.01" min="0" placeholder="0.00" required>
    </td>
    <td>
      <input type="text" name="variants[${addVariantCounter}][code]" class="form-control form-control-sm" value="${code}" placeholder="Auto">
    </td>
    <td class="text-center">
      <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="document.getElementById('${rowId}').remove()">
        <i class="bi bi-trash"></i>
      </button>
    </td>
  `;
  tbody.appendChild(tr);
}

async function handleEditProduct(id) {
  try {
    let prod = null;
    const el = document.getElementById('prod-data-' + id);
    if (el && el.textContent) {
      try { prod = JSON.parse(el.textContent); } catch (err) {}
    }
    if (!prod) {
      const res = await fetch('/resources/products/' + id + '/edit-data');
      if (res.ok) {
        prod = await res.json();
      }
    }
    if (prod) {
      openEditModal(prod);
    }
  } catch (e) {
    console.error('Error opening edit product modal:', e);
  }
}

async function handleManageVariants(id) {
  try {
    let prod = null;
    const el = document.getElementById('prod-data-' + id);
    if (el && el.textContent) {
      try { prod = JSON.parse(el.textContent); } catch (err) {}
    }
    if (!prod) {
      const res = await fetch('/resources/products/' + id + '/edit-data');
      if (res.ok) {
        prod = await res.json();
      }
    }
    if (prod) {
      openManageVariantsModal(prod);
    }
  } catch (e) {
    console.error('Error opening manage variants modal:', e);
  }
}

function openManageVariantsModal(prod) {
  const form = document.getElementById('manageVariantsForm');
  form.action = `/resources/products/${prod.id}/variants`;
  document.getElementById('manageVariantsTitle').innerHTML = `<i class="bi bi-layers text-primary me-2"></i>Sizes: <strong>${prod.name}</strong>`;

  const tbody = document.getElementById('manageVariantsTableBody');
  tbody.innerHTML = '';
  manageVariantCounter = 0;

  if (prod.variants && prod.variants.length > 0) {
    prod.variants.forEach(v => {
      addVariantRowInManageModal(v.variation_name || v.name, v.sale_price, v.code, v.id);
    });
  } else {
    addVariantRowInManageModal('Small', 650);
    addVariantRowInManageModal('Medium', 1050);
    addVariantRowInManageModal('Large', 1500);
  }

  const modalEl = document.getElementById('manageVariantsModal');
  const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
  modal.show();
}

function addVariantRowInManageModal(name = '', price = '', code = '', id = null) {
  const tbody = document.getElementById('manageVariantsTableBody');
  const rowId = `manage_var_${manageVariantCounter++}`;
  const tr = document.createElement('tr');
  tr.id = rowId;
  tr.innerHTML = `
    <td>
      ${id ? `<input type="hidden" name="variants[${manageVariantCounter}][id]" value="${id}">` : ''}
      <input type="text" name="variants[${manageVariantCounter}][name]" class="form-control form-control-sm" value="${name}" placeholder="e.g. Small / 1.5L" required>
    </td>
    <td>
      <input type="number" name="variants[${manageVariantCounter}][sale_price]" class="form-control form-control-sm" value="${price}" step="0.01" min="0" placeholder="0.00" required>
    </td>
    <td>
      <input type="text" name="variants[${manageVariantCounter}][code]" class="form-control form-control-sm" value="${code || ''}" placeholder="Auto">
    </td>
    <td class="text-center">
      <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="document.getElementById('${rowId}').remove()">
        <i class="bi bi-trash"></i>
      </button>
    </td>
  `;
  tbody.appendChild(tr);
}

function previewProductImage(input, previewContainerId) {
  const container = document.getElementById(previewContainerId);
  if (!container) return;
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = function(e) {
      container.innerHTML = `<img src="${e.target.result}" style="width: 100%; height: 100%; object-fit: cover;">`;
    };
    reader.readAsDataURL(input.files[0]);
  }
}

function openEditModal(prod) {
  const form = document.getElementById('editProductForm');
  form.action = `/resources/products/${prod.id}`;

  document.getElementById('edit_name').value = prod.name || '';
  document.getElementById('edit_name_ur').value = prod.name_ur || '';
  document.getElementById('edit_type').value = prod.type || 'menu_item';
  document.getElementById('edit_category_id').value = prod.category_id || '';
  document.getElementById('edit_cost_price').value = prod.cost_price || 0;
  document.getElementById('edit_sale_price').value = prod.sale_price || 0;
  document.getElementById('edit_min_stock').value = prod.min_stock || 0;
  document.getElementById('edit_prep_time_minutes').value = prod.prep_time_minutes || 15;

  // Image preview in edit modal
  const editPreview = document.getElementById('edit_image_preview_box');
  const removeWrapper = document.getElementById('edit_remove_image_wrapper');
  const removeCheckbox = document.getElementById('edit_remove_image');
  const imageInput = document.getElementById('edit_product_image');
  if (imageInput) imageInput.value = '';
  if (removeCheckbox) removeCheckbox.checked = false;

  if (prod.image) {
    const imgUrl = (prod.image.startsWith('http://') || prod.image.startsWith('https://')) ? prod.image : `/${prod.image}`;
    editPreview.innerHTML = `<img src="${imgUrl}" style="width: 100%; height: 100%; object-fit: cover;">`;
    if (removeWrapper) removeWrapper.style.display = 'block';
  } else {
    editPreview.innerHTML = `<i class="bi bi-camera text-muted fs-3"></i>`;
    if (removeWrapper) removeWrapper.style.display = 'none';
  }

  const modalEl = document.getElementById('editProductModal');
  const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
  modal.show();
}

function openRecipeModal(productId) {
  const modalEl = document.getElementById('recipeModal');
  const modal = new bootstrap.Modal(modalEl);
  const container = document.getElementById('recipeRowsContainer');
  container.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm text-primary me-2"></div> Loading recipe details...</td></tr>';
  
  modal.show();

  fetch(`/resources/products/${productId}/recipe`)
    .then(res => res.json())
    .then(data => {
      currentRecipeData = data;
      document.getElementById('recipeModalTitle').innerHTML = `<i class="bi bi-receipt me-2 text-primary"></i>Recipe BOM: <strong>${data.product.name}</strong>`;
      document.getElementById('recipeForm').action = `/resources/products/${productId}/recipe`;

      const tabsWrapper = document.getElementById('recipeVariantTabsWrapper');
      const pillsContainer = document.getElementById('recipeVariantPills');

      if (data.has_variants && data.variants && data.variants.length > 0) {
        tabsWrapper.style.display = 'block';
        pillsContainer.innerHTML = '';

        data.variants.forEach((v, idx) => {
          const btn = document.createElement('button');
          btn.type = 'button';
          btn.id = `recipe_variant_btn_${v.id}`;
          btn.className = `btn btn-sm ${idx === 0 ? 'btn-primary' : 'btn-outline-secondary'} px-3 py-1 rounded-pill`;
          btn.innerHTML = `<i class="bi bi-tag me-1"></i>${v.variation_name} <span class="badge bg-white text-dark ms-1">Rs. ${parseFloat(v.sale_price).toFixed(0)}</span>`;
          btn.onclick = () => switchRecipeVariant(v.id);
          pillsContainer.appendChild(btn);
        });

        // Activate first variant
        switchRecipeVariant(data.variants[0].id);
      } else {
        tabsWrapper.style.display = 'none';
        document.getElementById('recipeTargetProductId').value = data.product.id;
        currentDishSalePrice = parseFloat(data.product.sale_price) || 0.0;
        document.getElementById('modalSalePrice').innerText = `Rs. ${currentDishSalePrice.toFixed(2)}`;

        loadRecipeItemsIntoRows(data.recipe_items);
      }
    })
    .catch(err => {
      container.innerHTML = `<tr><td colspan="6" class="text-center text-danger py-4">Error loading recipe: ${err.message}</td></tr>`;
    });
}

function switchRecipeVariant(variantId) {
  if (!currentRecipeData || !currentRecipeData.variants) return;

  activeVariantId = variantId;
  const variant = currentRecipeData.variants.find(v => v.id == variantId);
  if (!variant) return;

  // Update pills styling
  const pills = document.querySelectorAll('#recipeVariantPills button');
  pills.forEach(btn => {
    if (btn.id === `recipe_variant_btn_${variantId}`) {
      btn.className = 'btn btn-sm btn-primary px-3 py-1 rounded-pill';
    } else {
      btn.className = 'btn btn-sm btn-outline-secondary px-3 py-1 rounded-pill';
    }
  });

  document.getElementById('recipeTargetProductId').value = variant.id;
  currentDishSalePrice = parseFloat(variant.sale_price) || 0.0;
  document.getElementById('modalSalePrice').innerText = `Rs. ${currentDishSalePrice.toFixed(2)}`;

  loadRecipeItemsIntoRows(variant.recipe_items);
}

function loadRecipeItemsIntoRows(items) {
  const container = document.getElementById('recipeRowsContainer');
  container.innerHTML = '';
  rowCounter = 0;

  if (items && items.length > 0) {
    items.forEach(item => {
      addRecipeIngredientRow(item.ingredient_id, item.quantity);
    });
  } else {
    // 1 empty row by default
    addRecipeIngredientRow();
  }

  calculateRecipeTotals();
}

function addRecipeIngredientRow(selectedIngredientId = null, initialQty = 1.0) {
  const container = document.getElementById('recipeRowsContainer');
  const rowId = `recipe_row_${rowCounter++}`;

  let optionsHtml = '<option value="">-- Select Raw Material / Ingredient --</option>';
  rawMaterialsList.forEach(rm => {
    const isSel = (selectedIngredientId && rm.id == selectedIngredientId) ? 'selected' : '';
    const unitCode = rm.unit ? (rm.unit.code || rm.unit.name) : 'units';
    optionsHtml += `<option value="${rm.id}" data-cost="${rm.cost_price}" data-unit="${unitCode}" ${isSel}>${rm.name} (${unitCode})</option>`;
  });

  const tr = document.createElement('tr');
  tr.id = rowId;
  tr.innerHTML = `
    <td>
      <select name="ingredients[${rowCounter}][ingredient_id]" class="form-select form-select-sm ingredient-select" required onchange="onIngredientChanged('${rowId}')">
        ${optionsHtml}
      </select>
    </td>
    <td>
      <span class="badge bg-light text-dark border unit-badge">Units</span>
    </td>
    <td>
      <input type="number" name="ingredients[${rowCounter}][quantity]" class="form-control form-control-sm qty-input" value="${initialQty}" step="0.0001" min="0.0001" required oninput="calculateRecipeTotals()">
    </td>
    <td class="unit-cost-cell text-muted small">
      Rs. 0.00
    </td>
    <td class="line-cost-cell fw-bold text-dark small">
      Rs. 0.00
    </td>
    <td class="text-center">
      <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="removeRecipeRow('${rowId}')">
        <i class="bi bi-trash"></i>
      </button>
    </td>
  `;

  container.appendChild(tr);
  onIngredientChanged(rowId);
}

function onIngredientChanged(rowId) {
  const row = document.getElementById(rowId);
  if (!row) return;

  const select = row.querySelector('.ingredient-select');
  const unitBadge = row.querySelector('.unit-badge');
  const unitCostCell = row.querySelector('.unit-cost-cell');

  const selectedOpt = select.selectedOptions[0];
  if (selectedOpt && selectedOpt.value) {
    const cost = parseFloat(selectedOpt.getAttribute('data-cost')) || 0.0;
    const unit = selectedOpt.getAttribute('data-unit') || 'Units';

    unitBadge.innerText = unit;
    unitCostCell.innerText = `Rs. ${cost.toFixed(2)}`;
  } else {
    unitBadge.innerText = 'Units';
    unitCostCell.innerText = 'Rs. 0.00';
  }

  calculateRecipeTotals();
}

function removeRecipeRow(rowId) {
  const row = document.getElementById(rowId);
  if (row) {
    row.remove();
    calculateRecipeTotals();
  }
}

function calculateRecipeTotals() {
  const rows = document.querySelectorAll('#recipeRowsContainer tr');
  let totalCost = 0.0;

  rows.forEach(row => {
    const select = row.querySelector('.ingredient-select');
    const qtyInput = row.querySelector('.qty-input');
    const lineCostCell = row.querySelector('.line-cost-cell');

    if (select && qtyInput && lineCostCell) {
      const selectedOpt = select.selectedOptions[0];
      const unitCost = selectedOpt ? (parseFloat(selectedOpt.getAttribute('data-cost')) || 0.0) : 0.0;
      const qty = parseFloat(qtyInput.value) || 0.0;
      const lineCost = qty * unitCost;

      lineCostCell.innerText = `Rs. ${lineCost.toFixed(2)}`;
      totalCost += lineCost;
    }
  });

  const grossProfit = currentDishSalePrice - totalCost;
  const marginPct = currentDishSalePrice > 0 ? (grossProfit / currentDishSalePrice) * 100 : 0.0;

  document.getElementById('modalRecipeCost').innerText = `Rs. ${totalCost.toFixed(2)}`;
  document.getElementById('modalGrossProfit').innerText = `Rs. ${grossProfit.toFixed(2)}`;
  document.getElementById('modalMarginPct').innerText = `${marginPct.toFixed(1)}%`;
}
</script>
@endpush
@endsection

