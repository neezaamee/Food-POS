<div>
  <!-- Top POS Navigation Bar -->
  <header class="pos-navbar">
    <div class="d-flex align-items-center gap-1 gap-sm-2 flex-shrink-0">
      <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-sm px-2 text-nowrap" title="Back to Backoffice">
        <i class="bi bi-arrow-left"></i>
        <span class="d-none d-md-inline ms-1">Dashboard</span>
      </a>
      <div class="d-flex align-items-center gap-1.5 gap-sm-2">
        <img src="{{ \App\Models\SystemSetting::logoUrl() }}" alt="{{ \App\Models\SystemSetting::get('restaurant_name', 'Food Point') }}" style="height: 24px; max-width: 75px; object-fit: contain;">
        <span class="fw-bold text-heading d-none d-lg-inline text-truncate" style="font-size: 0.88rem; max-width: 130px;" title="{{ \App\Models\SystemSetting::get('restaurant_name', 'Food Point POS') }}">
          {{ \App\Models\SystemSetting::get('restaurant_name', 'Food Point POS') }}
        </span>
      </div>

      <!-- Order Type Selector Pills -->
      <div class="btn-group btn-group-sm ms-1" role="group">
        <button type="button" 
          wire:click="setOrderType('TAKEAWAY')" 
          class="btn {{ $orderType === 'TAKEAWAY' ? 'btn-primary' : 'btn-outline-secondary' }} px-2 px-xl-3 py-1 text-nowrap"
          title="Takeaway Order">
          <i class="ph-duotone ph-shopping-bag me-1"></i><span class="d-none d-sm-inline">TAKEAWAY</span>
        </button>
        <button type="button" 
          wire:click="setOrderType('DINE_IN')" 
          class="btn {{ $orderType === 'DINE_IN' ? 'btn-primary' : 'btn-outline-secondary' }} px-2 px-xl-3 py-1 text-nowrap"
          title="Dine-in Order">
          <i class="ph-duotone ph-fork-knife me-1"></i><span class="d-none d-sm-inline">DINE-IN</span>
        </button>
        <button type="button" 
          wire:click="setOrderType('DELIVERY')" 
          class="btn {{ $orderType === 'DELIVERY' ? 'btn-primary' : 'btn-outline-secondary' }} px-2 px-xl-3 py-1 text-nowrap"
          title="Delivery Order">
          <i class="ph-duotone ph-moped me-1"></i><span class="d-none d-sm-inline">DELIVERY</span>
        </button>
      </div>
    </div>

    <!-- Center/Right Status -->
    <div class="d-flex align-items-center gap-1 gap-md-2 ms-auto flex-shrink-0">
      @if ($activeShift)
        <span class="badge badge-soft-success d-none d-sm-inline-flex align-items-center gap-1 py-1 px-2 text-nowrap" style="font-size: 0.75rem;" title="Shift #{{ $activeShift->id }} Open">
          <i class="ph-duotone ph-vault"></i> Shift #{{ $activeShift->id }}
        </span>
      @else
        <button type="button" wire:click="$set('showOpenShiftModal', true)" class="btn btn-warning btn-sm py-1 px-2 d-inline-flex align-items-center gap-1 fw-bold shadow-sm text-nowrap" style="font-size: 0.75rem;" title="Open Shift to Punch Orders">
          <i class="ph-duotone ph-warning"></i>
          <span>Open Shift<span class="d-none d-xl-inline"> to Punch</span></span>
        </button>
      @endif

      <!-- Network Status Pill (Online / Offline / Syncing) -->
      <span id="posNetworkStatusPill" class="badge bg-success-subtle text-success border border-success-subtle d-inline-flex align-items-center gap-1 px-2 py-1 text-nowrap" style="font-size: 0.75rem;" title="POS Network Status">
        <span class="p-1 rounded-circle bg-success"></span>
        <span class="d-none d-md-inline">Online</span>
      </span>

      <!-- WhatsApp Quick Status & Connect -->
      <button type="button" wire:click="openWhatsAppConnectModal" class="btn btn-outline-success btn-sm py-1 px-2 d-inline-flex align-items-center gap-1 text-nowrap" style="font-size: 0.75rem;" title="WhatsApp Receipt Status & Pair Device">
        <i class="bi bi-whatsapp"></i>
        <span class="d-none d-xl-inline">WhatsApp</span>
      </button>

      <!-- Offline Sync Badge & Trigger -->
      <button type="button" id="posOfflineSyncBtn" onclick="PosOfflineEngine.syncNow()" class="btn btn-warning btn-sm py-1 px-2 d-none align-items-center gap-1 shadow-sm fw-bold text-nowrap" style="font-size: 0.75rem;" title="Click to sync offline orders to cloud">
        <i class="bi bi-cloud-arrow-up-fill"></i>
        <span class="d-none d-xl-inline">Offline Queue</span>
        <span id="posOfflineQueueBadge" class="badge bg-dark rounded-pill ms-1">0</span>
      </button>

      <!-- Open Orders Drawer Trigger -->
      @php
        $openCount = \App\Models\Order::whereNull('finalized_at')->where('order_status', '!=', 'cancelled')->where('order_type', $orderType)->count();
      @endphp
      <button type="button" wire:click="$toggle('showOpenOrdersModal')" class="btn btn-outline-secondary btn-sm position-relative px-2 text-nowrap" title="Open {{ ucfirst(strtolower($orderType)) }} Orders">
        <i class="bi bi-clock-history"></i>
        <span class="d-none d-md-inline ms-1">
          <span class="d-none d-xxl-inline">{{ ucfirst(strtolower($orderType)) }} </span>Orders
        </span>
        @if ($openCount > 0)
          <span class="badge bg-primary ms-1 px-1.5">{{ $openCount }}</span>
        @endif
      </button>

      <!-- Refund / Invoices Quick Link -->
      <a href="{{ route('orders.index') }}" target="_blank" class="btn btn-outline-danger btn-sm px-2 text-nowrap" title="Refunds, Returns & Order History">
        <i class="bi bi-arrow-counter-clockwise"></i>
        <span class="d-none d-lg-inline ms-1">Refunds</span>
      </a>

      <!-- Theme Switcher -->
      <button class="header-action theme-toggle flex-shrink-0" title="Toggle Theme">
        <i class="bi bi-moon icon-dark"></i>
        <i class="bi bi-sun icon-light"></i>
      </button>

      <!-- Fullscreen -->
      <button class="header-action fullscreen-toggle flex-shrink-0" onclick="toggleFullscreen()" title="Fullscreen">
        <i class="bi bi-fullscreen icon-enter"></i>
        <i class="bi bi-fullscreen-exit icon-exit"></i>
      </button>

      <!-- Active User -->
      <div class="d-flex align-items-center gap-1.5 border-start ps-2 flex-shrink-0">
        <img src="{{ asset('assets/img/profile-img.webp') }}" class="rounded-circle" width="30" height="30" alt="Avatar">
        <div class="d-none d-xxl-block small text-start">
          <div class="fw-semibold text-truncate" style="max-width: 90px;">{{ auth()->user()->name ?? 'Cashier' }}</div>
          <div class="text-muted" style="font-size: 0.7rem; line-height: 1;">{{ ucfirst(auth()->user()->role ?? 'Staff') }}</div>
        </div>
      </div>
    </div>
  </header>

  <!-- POS Notification Toast -->
  @if ($notificationMessage)
    <div class="alert alert-{{ $notificationType }} py-2 px-3 small position-fixed top-0 start-50 translate-middle-x mt-3 shadow-lg z-3" role="alert">
      <div class="d-flex align-items-center gap-2">
        <i class="bi bi-info-circle-fill"></i>
        <span>{{ $notificationMessage }}</span>
        <button type="button" class="btn-close btn-close-sm ms-2" wire:click="$set('notificationMessage', '')"></button>
      </div>
    </div>
  @endif

  <!-- Main POS Grid Container -->
  <div class="pos-container">
    <!-- LEFT PANEL: Catalog & Search (60%) -->
    <div class="col-12 col-lg-6 col-xl-7 p-3 d-flex flex-column h-100 overflow-hidden border-end" style="background: var(--background-color);">
      <!-- Top Filters: Search & Barcode -->
      <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
        <div class="input-group input-group-sm flex-grow-1" style="max-width: 400px;">
          <span class="input-group-text bg-surface border-end-0"><i class="bi bi-search"></i></span>
          <input type="text" 
            wire:model.live.debounce.250ms="search" 
            class="form-control border-start-0" 
            placeholder="Search food by name, SKU or scan barcode..." 
            autofocus>
          @if ($search)
            <button class="btn btn-outline-secondary border-start-0" type="button" wire:click="$set('search', '')">
              <i class="bi bi-x"></i>
            </button>
          @endif
        </div>

        <!-- Dine-In Specific: Table Picker Button -->
        @if ($orderType === 'DINE_IN')
          <div class="d-flex align-items-center gap-2">
            <button type="button" wire:click="$set('selectedTableId', null)" class="badge {{ $selectedTableId ? 'bg-primary' : 'bg-danger' }} fs-6 py-2 px-3 border-0" style="cursor: pointer;" title="Click to select or change dining table">
              <i class="ph-duotone ph-chair me-1"></i>
              {{ $selectedTableName ? 'Table: ' . $selectedTableName : '⚠️ No Table Selected (Mandatory)' }}
            </button>
          </div>
        @endif

        <!-- Delivery Specific: Area Picker -->
        @if ($orderType === 'DELIVERY')
          <div class="d-flex align-items-center gap-2">
            <select class="form-select form-select-sm" wire:change="selectDeliveryArea($event.target.value)">
              <option value="">-- Select Delivery Area --</option>
              @foreach ($deliveryAreas as $area)
                <option value="{{ $area->id }}" {{ $deliveryAreaId == $area->id ? 'selected' : '' }}>
                  {{ $area->name }} (+ Rs. {{ number_format($area->delivery_charge) }})
                </option>
              @endforeach
            </select>
          </div>
        @endif
      </div>

      <!-- Categories Pills Scroll Bar -->
      <div class="d-flex gap-2 overflow-x-auto pb-2 mb-3 no-scrollbar" style="white-space: nowrap;">
        <button type="button" 
          wire:click="selectCategory(null)" 
          class="btn btn-sm {{ is_null($selectedCategoryId) ? 'btn-primary' : 'btn-outline-secondary' }} rounded-pill px-3">
          All Menu
        </button>
        @foreach ($categories as $cat)
          <button type="button" 
            wire:click="selectCategory({{ $cat->id }})" 
            class="btn btn-sm {{ $selectedCategoryId === $cat->id ? 'btn-primary' : 'btn-outline-secondary' }} rounded-pill px-3">
            {{ $cat->name }}
          </button>
        @endforeach
      </div>

      <!-- Dine-In Quick Table Grid (if Dine-In mode and no table selected or table view active) -->
      @if ($orderType === 'DINE_IN' && !$selectedTableId)
        <div class="alert alert-info py-2 px-3 mb-3 d-flex align-items-center justify-content-between">
          <span><i class="bi bi-info-circle me-1"></i> Please tap an available table below to begin dining order:</span>
        </div>
        <div class="overflow-y-auto flex-grow-1 pe-1">
          @foreach ($sections as $section)
            <div class="mb-3">
              <h6 class="text-uppercase small fw-bold text-muted mb-2">{{ $section->name }}</h6>
              <div class="row g-2">
                @foreach ($section->tables as $table)
                  <div class="col-6 col-sm-4 col-md-3">
                    <button type="button" 
                      wire:click="selectTable({{ $table->id }})"
                      class="card w-100 text-start p-2 border {{ $table->isOccupied() ? 'border-danger bg-danger-subtle' : 'border-success bg-surface' }} position-relative transition-base">
                      <div class="d-flex justify-content-between align-items-start">
                        <span class="fw-bold text-heading">{{ $table->table_number }}</span>
                        <span class="badge {{ $table->isOccupied() ? 'bg-danger' : 'bg-success' }}" style="font-size: 0.65rem;">
                          {{ ucfirst($table->status) }}
                        </span>
                      </div>
                      <div class="small text-muted mt-1">{{ $table->name }}</div>
                      <div class="small text-muted" style="font-size: 0.75rem;">Cap: {{ $table->capacity }} Pax</div>
                    </button>
                  </div>
                @endforeach
              </div>
            </div>
          @endforeach
        </div>
      @else
        <!-- Products Grid Scroll Area -->
        <div class="overflow-y-auto flex-grow-1 pe-1">
          <div class="row g-3">
            @forelse ($products as $prod)
              <div class="col-6 col-sm-4 col-md-3 col-xl-3">
                <div class="card h-100 product-card p-2 text-start position-relative d-flex flex-column justify-content-between border"
                  wire:click="addToCart({{ $prod->id }})"
                  style="cursor: pointer; transition: transform 0.15s ease, box-shadow 0.15s ease;"
                  onmouseover="this.style.transform='translateY(-2px)'"
                  onmouseout="this.style.transform='translateY(0)'">
                  <div>
                    <!-- Product Photo / Image if uploaded -->
                    @if ($prod->image_url)
                      <div class="rounded overflow-hidden mb-1.5 bg-light border" style="height: 58px;">
                        <img src="{{ $prod->image_url }}" alt="{{ $prod->name }}" style="width: 100%; height: 100%; object-fit: cover;">
                      </div>
                    @endif
                    <!-- Product Badge & Prep Time -->
                    <div class="d-flex justify-content-between align-items-center mb-1">
                      <span class="badge badge-soft-primary" style="font-size: 0.65rem;">
                        {{ $prod->category?->name ?? 'Item' }}
                      </span>
                      @if ($prod->has_variants && $prod->variants->isNotEmpty())
                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle" style="font-size: 0.65rem;">
                          <i class="bi bi-layers-fill me-1"></i>{{ $prod->variants->count() }} Sizes
                        </span>
                      @else
                        <small class="text-muted" style="font-size: 0.7rem;">
                          <i class="bi bi-stopwatch"></i> {{ $prod->prep_time_minutes }}m
                        </small>
                      @endif
                    </div>
                    <!-- Product Name -->
                    <div class="fw-semibold text-heading small text-truncate-2 mb-1" style="min-height: 2.2em; line-height: 1.18;">
                      {{ $prod->name }}
                    </div>
                  </div>
                  <!-- Price & Add -->
                  <div class="d-flex justify-content-between align-items-center mt-1 pt-1 border-top">
                    @if ($prod->has_variants && $prod->variants->isNotEmpty())
                      <div>
                        <div class="text-muted" style="font-size: 0.65rem; line-height: 1;">Starts from</div>
                        <span class="fw-bold fs-6 text-primary">Rs. {{ number_format($prod->variants->min('sale_price')) }}</span>
                      </div>
                      <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-2 py-0" style="font-size: 0.75rem; height: 24px;">
                        Choose <i class="bi bi-chevron-right ms-1"></i>
                      </button>
                    @else
                      <span class="fw-bold fs-6 text-primary">Rs. {{ number_format($prod->sale_price) }}</span>
                      <button type="button" class="btn btn-sm btn-primary rounded-circle p-0" style="width: 26px; height: 26px;">
                        <i class="bi bi-plus"></i>
                      </button>
                    @endif
                  </div>
                </div>
              </div>
            @empty
              <div class="col-12 text-center py-5 text-muted">
                <i class="bi bi-search fs-1 mb-2"></i>
                <p>No products found matching "{{ $search }}"</p>
              </div>
            @endforelse
          </div>
        </div>
      @endif
    </div>

    <!-- RIGHT PANEL: Order Cart & Checkout (40%) -->
    <div class="col-12 col-lg-6 col-xl-5 p-0 d-flex flex-column h-100 bg-surface">
      <!-- Order Header -->
      <div class="px-3 py-2 border-bottom d-flex align-items-center justify-content-between pos-cart-header">
        <div>
          <div class="d-flex align-items-center gap-2">
            <span class="badge bg-dark">{{ $orderType }}</span>
            <span class="fw-bold fs-6 text-heading">{{ $orderNumber }}</span>
          </div>
          <!-- Customer or Table Meta -->
          <div class="small text-muted" style="font-size: 0.75rem; margin-top: 2px;">
            @if ($orderType === 'DINE_IN')
              <i class="ph-duotone ph-fork-knife me-1"></i> {{ $selectedTableName ?: 'Select Table' }}
              @if ($selectedTableId)
                <a href="#" wire:click.prevent="$set('selectedTableId', null)" class="text-primary ms-1 small">(Change)</a>
              @endif
            @elseif ($orderType === 'DELIVERY')
              <i class="ph-duotone ph-moped me-1"></i> {{ $customerName }} ({{ $customerPhone ?: 'No Phone' }})
            @else
              <i class="ph-duotone ph-user me-1"></i> {{ $customerName }}
            @endif
          </div>
        </div>
        <div class="d-flex align-items-center gap-1">
          @if ($orderType === 'DINE_IN' && $selectedTableId && $currentOrderId)
            <button type="button" wire:click="openTableTransfer" class="btn btn-sm btn-outline-warning py-0.5 px-2" title="Transfer Table">
              <i class="bi bi-arrow-left-right"></i>
            </button>
          @endif
          <button type="button" wire:click="clearOrder" class="btn btn-sm btn-outline-danger py-0.5 px-2" title="Clear Order & All Form Fields">
            <i class="bi bi-arrow-clockwise me-1"></i> Clear
          </button>
        </div>
      </div>

      <!-- Customer Quick Edit (Mandatory Details) & Fast Item Code Entry -->
      <div class="px-3 py-1.5 border-bottom bg-light-subtle pos-customer-strip">
        <div class="row g-1.5 align-items-center">
          <div class="col-6">
            <input 
              type="tel" 
              wire:model.live.debounce.300ms="customerPhone" 
              id="posCustomerPhone"
              class="form-control form-control-sm" 
              placeholder="Mobile (0300...) *" 
              maxlength="11"
              pattern="[0-9]{11}"
              required
              oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11)"
              autocomplete="off"
              title="11-digit mobile number"
              onkeydown="if(event.key === 'Enter'){ event.preventDefault(); document.getElementById('posCustomerName')?.focus(); }">
          </div>
          <div class="col-6">
            <input 
              type="text" 
              wire:model.defer="customerName" 
              id="posCustomerName" 
              list="posCustomerList" 
              class="form-control form-control-sm" 
              placeholder="Customer Name *"
              required
              onkeydown="if(event.key === 'Enter'){ event.preventDefault(); if(document.getElementById('posCustomerAddress')){ document.getElementById('posCustomerAddress').focus(); } else { document.getElementById('posProductCodeInput')?.focus(); } }">
            <datalist id="posCustomerList">
              @foreach ($customers as $c)
                <option value="{{ $c->name }}">{{ $c->mobile }} ({{ $c->name }})</option>
              @endforeach
            </datalist>
          </div>
          @if ($orderType === 'DELIVERY' || !empty($customerAddress))
            <div class="col-12">
              <div class="input-group input-group-sm">
                <span class="input-group-text bg-white text-muted py-0 px-2" title="Delivery Address"><i class="bi bi-geo-alt-fill text-danger" style="font-size: 0.75rem;"></i></span>
                <input 
                  type="text" 
                  wire:model.live.debounce.300ms="customerAddress" 
                  id="posCustomerAddress"
                  class="form-control form-control-sm" 
                  placeholder="Delivery Address..."
                  title="Customer Address"
                  onkeydown="if(event.key === 'Enter'){ event.preventDefault(); document.getElementById('posProductCodeInput')?.focus(); }">
              </div>
            </div>
          @endif
          <!-- Fast Item Code / Barcode Punch Entry -->
          <div class="col-12">
            <div class="input-group input-group-sm">
              <span class="input-group-text bg-white text-primary fw-bold px-2 py-0" style="font-size: 0.72rem;">
                <i class="bi bi-upc-scan me-1"></i> Code
              </span>
              <input 
                type="text" 
                wire:model="productCodeInput" 
                id="posProductCodeInput"
                class="form-control form-control-sm fw-semibold" 
                placeholder="Item Code (e.g. BUR-01) & Enter"
                autocomplete="off"
                onkeydown="if(event.key === 'Enter'){ event.preventDefault(); @this.call('handleProductCodeEnter'); }">
              <button 
                type="button" 
                wire:click="handleProductCodeEnter" 
                class="btn btn-primary btn-sm px-2 fw-semibold" 
                style="font-size: 0.72rem;"
                title="Add item by code">
                <i class="bi bi-plus-lg"></i> Add
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Compact Delivery Summary Strip -->
      @if ($orderType === 'DELIVERY')
        <div class="px-3 py-1 bg-primary-subtle bg-opacity-25 border-bottom d-flex align-items-center justify-content-between small" style="min-height: 30px;">
          <div class="d-flex align-items-center gap-2 text-truncate pe-2" style="font-size: 0.75rem;">
            <i class="ph-duotone ph-moped text-primary fs-6 flex-shrink-0"></i>
            <span class="text-truncate">
              @if ($deliveryAreaId)
                <strong class="text-primary">{{ $deliveryAreas->firstWhere('id', $deliveryAreaId)?->name }}</strong>
                <span class="text-muted">(Fee: Rs. {{ number_format($deliveryCharge) }})</span>
                @if ($deliveryRiderId)
                  <span class="text-dark">• Rider: {{ $riders->firstWhere('id', $deliveryRiderId)?->name }}</span>
                @endif
              @else
                <span class="text-danger fw-semibold"><i class="bi bi-exclamation-circle me-1"></i> Area & Rider Required</span>
              @endif
            </span>
          </div>
          <button type="button" wire:click="$toggle('showDeliveryModal')" class="btn btn-primary btn-sm py-0 px-2 text-nowrap flex-shrink-0" style="font-size: 0.7rem; height: 22px; line-height: 20px;">
            <i class="bi bi-pencil-square me-1"></i> {{ $deliveryAreaId ? 'Edit' : 'Setup' }}
          </button>
        </div>
      @endif

      <!-- Compact Dine-In Table Strip -->
      @if ($orderType === 'DINE_IN')
        <div class="px-3 py-1 {{ $selectedTableId ? 'bg-success-subtle bg-opacity-25' : 'bg-danger-subtle' }} border-bottom d-flex align-items-center justify-content-between small" style="min-height: 30px;">
          <div class="d-flex align-items-center gap-2 text-truncate pe-2" style="font-size: 0.75rem;">
            <i class="ph-duotone ph-chair {{ $selectedTableId ? 'text-success' : 'text-danger' }} fs-6 flex-shrink-0"></i>
            <span class="text-truncate">
              @if ($selectedTableId)
                <strong class="text-success">Table: {{ $selectedTableName }}</strong>
              @else
                <span class="text-danger fw-bold"><i class="bi bi-exclamation-triangle-fill me-1"></i> Table Required</span>
              @endif
            </span>
          </div>
          <button type="button" wire:click="$set('selectedTableId', null)" class="btn {{ $selectedTableId ? 'btn-outline-secondary' : 'btn-danger' }} btn-sm py-0 px-2 text-nowrap flex-shrink-0" style="font-size: 0.7rem; height: 22px; line-height: 20px;">
            <i class="bi bi-grid-fill me-1"></i> {{ $selectedTableId ? 'Change' : 'Select' }}
          </button>
        </div>
      @endif

      <!-- Cart Items Scroll List (Dense 32px rows for small/14-inch screens) -->
      <div class="flex-grow-1 overflow-y-auto px-3 py-1" id="posCartScrollContainer">
        @if (empty($cart))
          <div class="h-100 d-flex flex-column align-items-center justify-content-center text-muted py-4">
            <i class="ph-duotone ph-shopping-cart fs-1 mb-2 opacity-50"></i>
            <p class="mb-0 small">Your order cart is empty.</p>
            <small class="text-muted" style="font-size: 0.72rem;">Tap products from the menu to add items.</small>
          </div>
        @else
          <div class="d-flex flex-column">
            @foreach ($cart as $index => $item)
              <div class="pos-cart-item py-1.5 px-0 border-bottom">
                <div class="d-flex align-items-center justify-content-between gap-1.5">
                  <!-- Stepper & Item Info -->
                  <div class="d-flex align-items-center gap-2 flex-grow-1 min-w-0">
                    <div class="input-group input-group-sm flex-shrink-0" style="width: 76px; height: 26px;">
                      <button type="button" wire:click="updateQty({{ $index }}, -1)" class="btn btn-outline-secondary px-1.5 py-0" style="font-size: 0.75rem; line-height: 1;">-</button>
                      <input type="text" wire:change="setQty({{ $index }}, $event.target.value)" value="{{ $item['qty'] }}" class="form-control text-center p-0 fw-bold" style="font-size: 0.78rem;">
                      <button type="button" wire:click="updateQty({{ $index }}, 1)" class="btn btn-outline-secondary px-1.5 py-0" style="font-size: 0.75rem; line-height: 1;">+</button>
                    </div>
                    <div class="min-w-0 flex-grow-1">
                      <div class="fw-semibold text-heading text-truncate" style="font-size: 0.82rem; line-height: 1.2;" title="{{ $item['name'] }}">
                        {{ $item['name'] }}
                      </div>
                      <div class="text-muted" style="font-size: 0.7rem; line-height: 1;">
                        Rs. {{ number_format($item['price']) }} each
                      </div>
                    </div>
                  </div>

                  <!-- Subtotal & Actions -->
                  <div class="d-flex align-items-center gap-1.5 flex-shrink-0">
                    <span class="fw-bold text-heading" style="font-size: 0.85rem;">
                      Rs. {{ number_format($item['price'] * $item['qty']) }}
                    </span>
                    <button type="button" wire:click="openItemNote({{ $index }})" class="btn btn-sm btn-outline-secondary p-0 d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;" title="Cooking Note">
                      <i class="bi {{ !empty($item['notes']) ? 'bi-chat-dots-fill text-warning' : 'bi-pencil' }}" style="font-size: 0.75rem;"></i>
                    </button>
                    <button type="button" wire:click="removeFromCart({{ $index }})" class="btn btn-sm text-danger p-0 d-flex align-items-center justify-content-center" style="width: 22px; height: 22px;" title="Remove Item">
                      <i class="bi bi-x-circle" style="font-size: 0.9rem;"></i>
                    </button>
                  </div>
                </div>

                <!-- Special Cooking Note preview -->
                @if (!empty($item['notes']))
                  <div class="small text-warning-emphasis bg-warning-subtle px-2 py-0.5 rounded mt-1 d-flex align-items-center gap-1" style="font-size: 0.72rem;">
                    <i class="bi bi-chat-dots-fill"></i>
                    <span class="text-truncate">{{ $item['notes'] }}</span>
                  </div>
                @endif
              </div>
            @endforeach
          </div>
        @endif
      </div>

      <!-- Financial Calculation Summary Box (Compact for 14-inch screens) -->
      <div class="p-2.5 border-top bg-surface pos-summary-box">
        <div class="d-flex justify-content-between small text-muted mb-1" style="font-size: 0.78rem;">
          <span>Subtotal</span>
          <span id="posCartSubtotal" class="fw-semibold text-heading">Rs. {{ number_format($subtotal, 2) }}</span>
        </div>

        <!-- Discount Row -->
        <div class="d-flex justify-content-between align-items-center small mb-1" style="font-size: 0.78rem;">
          <div class="d-flex align-items-center gap-1">
            <span class="text-muted">Discount</span>
            <select wire:model.live="discountType" id="posDiscountType" class="form-select form-select-sm p-0 px-1" style="width: 48px; height: 22px; font-size: 0.7rem;">
              <option value="fixed">Rs.</option>
              <option value="percent">%</option>
            </select>
            <input type="number" wire:model.live.debounce.300ms="discountRate" id="posDiscountRate" class="form-control form-control-sm p-0 px-1 text-end" style="width: 55px; height: 22px; font-size: 0.75rem;" min="0">
          </div>
          <span class="text-danger fw-semibold" id="posCartDiscount">- Rs. {{ number_format($discountAmount, 2) }}</span>
        </div>

        <!-- Delivery Fee Row (if delivery) -->
        @if ($orderType === 'DELIVERY')
          <div class="d-flex justify-content-between small text-muted mb-1" id="posDeliveryFeeRow" style="font-size: 0.78rem;">
            <span>Delivery Fee</span>
            <span id="posCartDeliveryFee" class="fw-semibold text-heading">+ Rs. {{ number_format($deliveryCharge, 2) }}</span>
          </div>
        @endif

        <!-- Tax Row -->
        @if ($taxAmount > 0)
          <div class="d-flex justify-content-between small text-muted mb-1" id="posTaxRow" style="font-size: 0.78rem;">
            <span>Tax ({{ $taxRate }}%)</span>
            <span id="posCartTax" class="fw-semibold text-heading">+ Rs. {{ number_format($taxAmount, 2) }}</span>
          </div>
        @endif

        <!-- Grand Total Hero -->
        <div class="d-flex justify-content-between align-items-baseline pt-1.5 border-top mt-1">
          <span class="fw-bold fs-6 text-heading">Grand Total</span>
          <span class="fw-bold fs-5 text-primary" id="posCartGrandTotal">Rs. {{ number_format($grandTotal, 2) }}</span>
        </div>

        @if (! $activeShift)
          <div class="alert alert-warning py-1 px-2 small mt-1.5 mb-0 d-flex align-items-center justify-content-between" style="font-size: 0.72rem;">
            <div>
              <i class="bi bi-exclamation-triangle-fill me-1"></i> <strong>Shift Closed</strong>
            </div>
            <button type="button" wire:click="$set('showOpenShiftModal', true)" class="btn btn-dark btn-sm py-0.5 px-2 fw-bold" style="font-size: 0.7rem;">
              Open Shift
            </button>
          </div>
        @endif

        @if ($orderType === 'DINE_IN' && ! $selectedTableId)
          <div class="alert alert-danger py-1 px-2 small mt-1.5 mb-0 d-flex align-items-center justify-content-between" id="posTableRequiredAlert" style="font-size: 0.72rem;">
            <span><i class="bi bi-exclamation-triangle-fill me-1"></i> <strong>Table Required:</strong> Select a table.</span>
            <button type="button" wire:click="$set('selectedTableId', null)" class="btn btn-danger btn-sm py-0 px-2 fw-bold" style="font-size: 0.68rem;">Choose Table</button>
          </div>
        @endif

        <!-- Action Buttons Grid (Compact 36px height) -->
        <div class="row g-1.5 mt-1">
          <div class="col-3">
            <button 
              type="button" 
              wire:click="saveOpenOrder" 
              id="posSaveOrderBtn"
              class="btn btn-outline-secondary w-100 py-1.5 px-1 text-center pos-action-btn d-flex align-items-center justify-content-center gap-1" 
              {{ empty($cart) ? 'disabled' : '' }} 
              title="Save / Punch order to open orders (Enter)"
              onkeydown="if(event.key === 'Enter'){ event.preventDefault(); this.click(); }">
              <i class="ph-duotone ph-floppy-disk fs-6"></i>
              <span class="fw-semibold" style="font-size: 0.75rem;">Save</span>
            </button>
          </div>
          <div class="col-4">
            <button 
              type="button" 
              wire:click="saveAndPrintUnpaidBill" 
              id="posPrintBillBtn"
              class="btn btn-outline-dark w-100 py-1.5 px-1 text-center pos-action-btn d-flex align-items-center justify-content-center gap-1" 
              {{ empty($cart) ? 'disabled' : '' }} 
              title="Save & print unpaid bill (Enter)"
              onkeydown="if(event.key === 'Enter'){ event.preventDefault(); this.click(); }">
              <i class="bi bi-printer fs-6"></i>
              <span class="fw-bold" style="font-size: 0.75rem;">Bill</span>
            </button>
          </div>
          <div class="col-5">
            <button 
              type="button" 
              wire:click="openCheckout" 
              id="posPayBtn"
              class="btn btn-primary w-100 py-1.5 px-1 text-center fw-bold pos-action-btn d-flex align-items-center justify-content-center gap-1" 
              {{ empty($cart) ? 'disabled' : '' }} 
              title="Take payment and complete checkout"
              onkeydown="if(event.key === 'Enter'){ event.preventDefault(); this.click(); }">
              <i class="ph-duotone ph-credit-card fs-6"></i>
              <span class="fw-bold text-uppercase" style="font-size: 0.78rem;">PAY</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- CHECKOUT MODAL -->
  @if ($showCheckoutModal)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.6);" aria-modal="true" role="dialog" wire:click.self="closeCheckout">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border">
          <div class="modal-header">
            <h5 class="modal-title fw-bold">
              <i class="ph-duotone ph-cash-register me-2 text-primary"></i>
              Checkout - Order #{{ $orderNumber }} ({{ $orderType }})
            </h5>
            <button type="button" class="btn-close" wire:click="closeCheckout"></button>
          </div>
          <div class="modal-body p-4">
            <div class="row g-4">
              <!-- Left: Order Summary -->
              <div class="col-md-5 border-end">
                <h6 class="text-uppercase small text-muted fw-bold mb-3">Order Summary</h6>
                <div class="d-flex justify-content-between mb-2">
                  <span class="text-muted">Subtotal:</span>
                  <span>Rs. {{ number_format($subtotal, 2) }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2 text-danger">
                  <span>Discount:</span>
                  <span>- Rs. {{ number_format($discountAmount, 2) }}</span>
                </div>
                @if ($orderType === 'DELIVERY')
                  <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Delivery Charges:</span>
                    <span>Rs. {{ number_format($deliveryCharge, 2) }}</span>
                  </div>
                @endif
                <div class="d-flex justify-content-between mb-2">
                  <span class="text-muted">Total Items:</span>
                  <span>{{ count($cart) }} lines</span>
                </div>
                <hr>
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <span class="fw-bold fs-5">Payable:</span>
                  <span class="fw-bold fs-4 text-primary">Rs. {{ number_format($grandTotal, 2) }}</span>
                </div>

                @if ($paymentMethod === 'cash')
                  <div class="p-3 bg-light-subtle rounded border">
                    <div class="d-flex justify-content-between small mb-1">
                      <span class="text-muted">Cash Tendered:</span>
                      <span class="fw-semibold">Rs. {{ number_format($tenderedAmount, 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between fs-6 fw-bold text-success">
                      <span>Change Due:</span>
                      <span>Rs. {{ number_format(max(0, $tenderedAmount - $grandTotal), 2) }}</span>
                    </div>
                  </div>
                @endif
              </div>

              <!-- Right: Payment Methods & Cash Tender -->
              <div class="col-md-7">
                <h6 class="text-uppercase small text-muted fw-bold mb-3">Payment Method</h6>
                
                <!-- Method Pills -->
                <div class="btn-group w-100 mb-3" role="group">
                  <button type="button" wire:click="$set('paymentMethod', 'cash')" class="btn {{ $paymentMethod === 'cash' ? 'btn-primary' : 'btn-outline-secondary' }}">
                    <i class="ph-duotone ph-money me-1"></i> Cash
                  </button>
                  <button type="button" wire:click="$set('paymentMethod', 'card')" class="btn {{ $paymentMethod === 'card' ? 'btn-primary' : 'btn-outline-secondary' }}">
                    <i class="ph-duotone ph-credit-card me-1"></i> Card
                  </button>
                  <button type="button" wire:click="$set('paymentMethod', 'bank')" class="btn {{ $paymentMethod === 'bank' ? 'btn-primary' : 'btn-outline-secondary' }}">
                    <i class="ph-duotone ph-bank me-1"></i> Bank
                  </button>
                  <button type="button" wire:click="$set('paymentMethod', 'digital')" class="btn {{ $paymentMethod === 'digital' ? 'btn-primary' : 'btn-outline-secondary' }}">
                    <i class="ph-duotone ph-device-mobile me-1"></i> Wallet / QR
                  </button>
                  <button type="button" wire:click="$set('paymentMethod', 'credit')" class="btn {{ $paymentMethod === 'credit' ? 'btn-primary' : 'btn-outline-secondary' }}">
                    <i class="ph-duotone ph-handshake me-1"></i> Credit
                  </button>
                  <button type="button" wire:click="$set('paymentMethod', 'split')" class="btn {{ $paymentMethod === 'split' ? 'btn-primary' : 'btn-outline-secondary' }}">
                    <i class="ph-duotone ph-arrows-split me-1"></i> Split
                  </button>
                </div>

                {{-- Digital Wallet / Third-Party Provider View --}}
                @if ($paymentMethod === 'digital')
                  <div class="p-3 border rounded bg-light-subtle mb-3">
                    <label class="form-label small fw-bold text-dark mb-2">Select Digital Wallet Provider</label>
                    <div class="row g-2 mb-3">
                      <div class="col-6 col-md-3">
                        <button type="button" wire:click="$set('digitalProvider', 'jazzcash')" class="btn btn-sm w-100 py-2 border {{ $digitalProvider === 'jazzcash' ? 'bg-warning bg-opacity-25 border-warning fw-bold text-dark' : 'bg-white text-muted' }}">
                          🟠 JazzCash
                        </button>
                      </div>
                      <div class="col-6 col-md-3">
                        <button type="button" wire:click="$set('digitalProvider', 'easypaisa')" class="btn btn-sm w-100 py-2 border {{ $digitalProvider === 'easypaisa' ? 'bg-success bg-opacity-25 border-success fw-bold text-dark' : 'bg-white text-muted' }}">
                          🟢 EasyPaisa
                        </button>
                      </div>
                      <div class="col-6 col-md-3">
                        <button type="button" wire:click="$set('digitalProvider', 'nayapay')" class="btn btn-sm w-100 py-2 border {{ $digitalProvider === 'nayapay' ? 'bg-info bg-opacity-25 border-info fw-bold text-dark' : 'bg-white text-muted' }}">
                          🔵 NayaPay
                        </button>
                      </div>
                      <div class="col-6 col-md-3">
                        <button type="button" wire:click="$set('digitalProvider', 'raast')" class="btn btn-sm w-100 py-2 border {{ $digitalProvider === 'raast' ? 'bg-primary bg-opacity-25 border-primary fw-bold text-dark' : 'bg-white text-muted' }}">
                          🟣 Raast QR
                        </button>
                      </div>
                    </div>

                    {{-- Channel Toggle: Push Request vs Dynamic QR --}}
                    <div class="d-flex gap-2 mb-3">
                      <button type="button" wire:click="$set('digitalPaymentChannel', 'push_request')" class="btn btn-xs btn-sm flex-fill py-1 {{ $digitalPaymentChannel === 'push_request' ? 'btn-dark' : 'btn-outline-secondary' }}">
                        <i class="bi bi-phone me-1"></i> Mobile Push Prompt
                      </button>
                      <button type="button" wire:click="$set('digitalPaymentChannel', 'dynamic_qr')" class="btn btn-xs btn-sm flex-fill py-1 {{ $digitalPaymentChannel === 'dynamic_qr' ? 'btn-dark' : 'btn-outline-secondary' }}">
                        <i class="bi bi-qr-code me-1"></i> Dynamic QR Scan
                      </button>
                    </div>

                    {{-- Push Request Form --}}
                    @if ($digitalPaymentChannel === 'push_request')
                      <div class="mb-2">
                        <label class="form-label small fw-semibold">Customer Wallet Number</label>
                        <div class="input-group input-group-sm">
                          <span class="input-group-text bg-white"><i class="bi bi-telephone"></i></span>
                          <input type="text" wire:model.defer="customerWalletMobile" class="form-control" placeholder="03001234567">
                          <button type="button" wire:click="initiateDigitalPayment" class="btn btn-primary px-3">
                            <i class="bi bi-send me-1"></i> Send Prompt
                          </button>
                        </div>
                        <div class="form-text small">Dispatches an instant USSD or App MPIN authorization request to the customer.</div>
                      </div>
                    @else
                      {{-- Dynamic QR Generation --}}
                      <div class="text-center py-2">
                        @if ($generatedQrPayload)
                          <div class="p-3 bg-white border rounded d-inline-block shadow-sm mb-2">
                            <div class="font-monospace small text-dark p-2 bg-light border rounded" style="word-break: break-all; max-width: 280px;">
                              <i class="bi bi-qr-code fs-1 d-block text-primary mb-1"></i>
                              <span class="fw-bold text-uppercase">{{ $digitalProvider }} Dynamic QR</span><br>
                              <span class="text-muted" style="font-size: 0.7rem;">Amount: Rs. {{ number_format($grandTotal, 2) }}</span>
                            </div>
                          </div>
                          <div class="small text-muted">Customer scans this QR code with their banking app to pay.</div>
                        @else
                          <button type="button" wire:click="initiateDigitalPayment" class="btn btn-sm btn-primary px-3">
                            <i class="bi bi-qr-code me-1"></i> Generate Dynamic Payment QR
                          </button>
                        @endif
                      </div>
                    @endif

                    {{-- Transaction & Status Feedback --}}
                    @if ($digitalPaymentState !== 'idle')
                      <div class="alert small mb-2 border-0 {{ $digitalPaymentState === 'approved' ? 'alert-success' : ($digitalPaymentState === 'failed' ? 'alert-danger' : 'alert-warning') }}">
                        <div class="d-flex align-items-center justify-content-between">
                          <span>
                            @if ($digitalPaymentState === 'approved')
                              <i class="bi bi-check-circle-fill me-1 text-success"></i>
                            @elseif ($digitalPaymentState === 'failed')
                              <i class="bi bi-x-circle-fill me-1 text-danger"></i>
                            @else
                              <span class="spinner-border spinner-border-sm me-1"></span>
                            @endif
                            {{ $digitalPaymentMessage }}
                          </span>
                          <button type="button" wire:click="resetDigitalPayment" class="btn btn-xs btn-link text-muted p-0 ms-2">Reset</button>
                        </div>
                      </div>
                    @endif

                    {{-- Testing & Sandbox Interactive Simulator Widget --}}
                    <div class="p-2 border rounded bg-white mt-3">
                      <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small fw-bold text-muted"><i class="bi bi-tools text-warning me-1"></i>Testing Simulator Sandbox</span>
                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle" style="font-size: 0.65rem;">Simulated Test Triggers</span>
                      </div>
                      <div class="d-flex flex-wrap gap-1 mt-2">
                        <button type="button" wire:click="simulateDigitalApproval" class="btn btn-xs btn-sm btn-success flex-fill py-1 fw-semibold">
                          <i class="bi bi-check2-circle me-1"></i> Simulate Approved
                        </button>
                        <button type="button" wire:click="simulateDigitalFailure('Customer entered incorrect MPIN')" class="btn btn-xs btn-sm btn-outline-danger flex-fill py-1">
                          <i class="bi bi-x-circle me-1"></i> Simulate Declined
                        </button>
                        <button type="button" wire:click="simulateDigitalTimeout" class="btn btn-xs btn-sm btn-outline-secondary flex-fill py-1">
                          <i class="bi bi-hourglass-bottom me-1"></i> Timeout
                        </button>
                      </div>
                    </div>

                    {{-- Transaction Reference / TID input --}}
                    <div class="mt-3">
                      <label class="form-label small fw-semibold">Gateway Transaction ID (TID / Auth Code)</label>
                      <input type="text" wire:model.defer="paymentReference" class="form-control form-control-sm font-monospace fw-bold" placeholder="e.g. JC-TX-849201">
                    </div>
                  </div>
                @endif

                <!-- Single Payment Input (Non-digital, non-split) -->
                @if ($paymentMethod !== 'split' && $paymentMethod !== 'digital')
                  <div class="mb-3">
                    <label class="form-label small fw-semibold">Amount Tendered</label>
                    <input type="number" wire:model.live="tenderedAmount" class="form-control form-control-lg text-end fw-bold" step="0.01">
                  </div>

                  <!-- Quick Tender Cash Buttons -->
                  @if ($paymentMethod === 'cash')
                    <div class="mb-3">
                      <label class="form-label small text-muted">Quick Tender:</label>
                      <div class="d-flex flex-wrap gap-2">
                        <button type="button" wire:click="setQuickTender({{ $grandTotal }})" class="btn btn-sm btn-outline-primary">Exact</button>
                        <button type="button" wire:click="setQuickTender(500)" class="btn btn-sm btn-outline-secondary">500</button>
                        <button type="button" wire:click="setQuickTender(1000)" class="btn btn-sm btn-outline-secondary">1,000</button>
                        <button type="button" wire:click="setQuickTender(2000)" class="btn btn-sm btn-outline-secondary">2,000</button>
                        <button type="button" wire:click="setQuickTender(5000)" class="btn btn-sm btn-outline-secondary">5,000</button>
                      </div>
                    </div>
                  @endif

                  @if ($paymentMethod !== 'cash')
                    <div class="mb-3">
                      <label class="form-label small fw-semibold">Transaction Reference / Auth Code</label>
                      <input type="text" wire:model.defer="paymentReference" class="form-control" placeholder="e.g. Card slip # or Bank approval code">
                    </div>
                  @endif

                @elseif ($paymentMethod === 'split')
                  <!-- Split Payment Rows -->
                  <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                      <label class="form-label small fw-semibold mb-0">Split Tender Breakdown</label>
                      <button type="button" wire:click="addSplitPaymentRow" class="btn btn-sm btn-outline-primary py-0 px-2">+ Add Row</button>
                    </div>
                    @foreach ($splitPayments as $pIndex => $pRow)
                      <div class="d-flex gap-2 mb-2 align-items-center">
                        <select wire:model.defer="splitPayments.{{ $pIndex }}.method" class="form-select form-select-sm" style="width: 120px;">
                          <option value="cash">Cash</option>
                          <option value="card">Card</option>
                          <option value="bank">Bank</option>
                          <option value="jazzcash">JazzCash</option>
                          <option value="easypaisa">EasyPaisa</option>
                          <option value="nayapay">NayaPay</option>
                          <option value="raast">Raast QR</option>
                          <option value="credit">Credit</option>
                        </select>
                        <input type="number" wire:model.defer="splitPayments.{{ $pIndex }}.amount" class="form-control form-control-sm text-end" placeholder="Amount" step="0.01">
                        <input type="text" wire:model.defer="splitPayments.{{ $pIndex }}.reference" class="form-control form-control-sm" placeholder="Ref/TID #">
                        @if (count($splitPayments) > 1)
                          <button type="button" wire:click="removeSplitPaymentRow({{ $pIndex }})" class="btn btn-sm text-danger p-0"><i class="bi bi-trash"></i></button>
                        @endif
                      </div>
                    @endforeach
                  </div>
                @endif
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" wire:click="closeCheckout">Cancel</button>
            <button type="button" class="btn btn-primary px-4 py-2 fw-bold" wire:click="processCheckout">
              <i class="ph-duotone ph-check-circle me-1"></i> CONFIRM & CASH OUT
            </button>
          </div>
        </div>
      </div>
    </div>
  @endif

  <!-- ITEM NOTE MODAL -->
  @if ($showNoteModal)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);" wire:click.self="$set('showNoteModal', false)">
      <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
          <div class="modal-header py-2">
            <h6 class="modal-title fw-bold">Cooking Instructions / Note</h6>
            <button type="button" class="btn-close" wire:click="$set('showNoteModal', false)"></button>
          </div>
          <div class="modal-body p-3">
            <textarea wire:model.defer="editingItemNote" class="form-control" rows="3" placeholder="e.g. No Mayo, Extra Sauce, Spicy..."></textarea>
          </div>
          <div class="modal-footer py-2">
            <button type="button" class="btn btn-sm btn-secondary" wire:click="$set('showNoteModal', false)">Cancel</button>
            <button type="button" class="btn btn-sm btn-primary" wire:click="saveItemNote">Save Note</button>
          </div>
        </div>
      </div>
    </div>
  @endif

  <!-- TABLE TRANSFER MODAL -->
  @if ($showTableTransferModal)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);" wire:click.self="$set('showTableTransferModal', false)">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title fw-bold">Transfer Table</h5>
            <button type="button" class="btn-close" wire:click="$set('showTableTransferModal', false)"></button>
          </div>
          <div class="modal-body p-3">
            <p class="small text-muted">Select an available table to move Order #{{ $orderNumber }} from <strong>{{ $selectedTableName }}</strong>:</p>
            <div class="mb-3">
              <select wire:model.defer="transferToTableId" class="form-select">
                <option value="">-- Choose Target Table --</option>
                @foreach ($sections as $sec)
                  <optgroup label="{{ $sec->name }}">
                    @foreach ($sec->tables->where('status', 'available') as $availTable)
                      <option value="{{ $availTable->id }}">{{ $availTable->table_number }} - {{ $availTable->name }} (Cap: {{ $availTable->capacity }})</option>
                    @endforeach
                  </optgroup>
                @endforeach
              </select>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" wire:click="$set('showTableTransferModal', false)">Cancel</button>
            <button type="button" class="btn btn-warning fw-bold" wire:click="executeTableTransfer">Execute Transfer</button>
          </div>
        </div>
      </div>
    </div>
  @endif

  <!-- RECEIPT MODAL (Thermal 80mm Print Layout) -->
  @if ($showReceiptModal && $completedOrder)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.7);" wire:click.self="closeReceiptModal">
      <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
        <div class="modal-content">
          <div class="modal-header py-2 no-print">
            <h6 class="modal-title fw-bold">Customer Receipt</h6>
            <button type="button" class="btn-close" wire:click="closeReceiptModal"></button>
          </div>
          <div class="modal-body p-3" id="printableReceipt" style="font-family: 'Courier New', Courier, monospace, 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq'; font-size: 14px; font-weight: 600; line-height: 1.35;">
            <!-- Header -->
            <div class="text-center mb-2">
              <img src="{{ \App\Models\SystemSetting::logoUrl() }}" style="max-height: 48px; max-width: 140px; object-fit: contain; margin-bottom: 4px;" alt="Logo"><br>
              <h5 class="fw-bold mb-0 text-uppercase" style="font-size: 18px; font-weight: 900; letter-spacing: 0.5px;">{{ \App\Models\SystemSetting::get('restaurant_name', 'FOOD POINT RESTAURANT') }}</h5>
              @if (\App\Models\SystemSetting::get('tagline'))
                <div style="font-size: 12px; font-weight: bold;">{{ \App\Models\SystemSetting::get('tagline') }}</div>
              @endif
              @if (\App\Models\SystemSetting::get('restaurant_address'))
                <div style="font-size: 12px;">{{ \App\Models\SystemSetting::get('restaurant_address') }}</div>
              @endif
              @if (\App\Models\SystemSetting::get('restaurant_phone'))
                <div style="font-size: 12px; font-weight: bold;">Tel: {{ \App\Models\SystemSetting::get('restaurant_phone') }}</div>
              @endif
              @if (\App\Models\SystemSetting::get('ntn_number') || \App\Models\SystemSetting::get('strn_number'))
                <div style="font-size: 11px;">
                  @if (\App\Models\SystemSetting::get('ntn_number')) NTN: {{ \App\Models\SystemSetting::get('ntn_number') }} @endif
                  @if (\App\Models\SystemSetting::get('strn_number')) | STRN: {{ \App\Models\SystemSetting::get('strn_number') }} @endif
                </div>
              @endif
              <div class="mt-2 py-1 fw-bold" style="border: 2px solid #000; font-size: 15px; display: inline-block; padding: 2px 8px; margin: 4px 0;">
                *** {{ $completedOrder->order_type }} RECEIPT ***
              </div>
            </div>

            <!-- Meta Info -->
            <div class="mb-2" style="border-top: 1.5px dashed #000; border-bottom: 1.5px dashed #000; padding: 4px 0;">
              <div class="d-flex justify-content-between">
                <span>Order #:</span>
                <strong style="font-size: 16px;">{{ $completedOrder->order_number }}</strong>
              </div>
              <div class="d-flex justify-content-between" style="font-size: 13px;">
                <span>Date:</span>
                <span>{{ $completedOrder->finalized_at?->format('d/m/Y H:i') }}</span>
              </div>
              @if ($completedOrder->table_name)
                <div class="d-flex justify-content-between fw-bold">
                  <span style="font-size: 14px;">Table:</span>
                  <strong style="font-size: 17px;">{{ $completedOrder->table_name }}</strong>
                </div>
              @endif
              @if ($completedOrder->customer_name)
                <div class="d-flex justify-content-between" style="font-size: 13px;">
                  <span>Customer:</span>
                  <strong>{{ $completedOrder->customer_name }}</strong>
                </div>
              @endif
              @if ($completedOrder->customer_phone)
                <div class="d-flex justify-content-between" style="font-size: 13px;">
                  <span>Phone:</span>
                  <strong>{{ $completedOrder->customer_phone }}</strong>
                </div>
              @endif
              @if ($completedOrder->order_type === 'DELIVERY')
                @if ($completedOrder->deliveryArea)
                  <div class="d-flex justify-content-between" style="font-size: 13px;">
                    <span>Area:</span>
                    <strong>{{ $completedOrder->deliveryArea->name }}</strong>
                  </div>
                @endif
                @if ($completedOrder->rider)
                  <div class="d-flex justify-content-between" style="font-size: 13px;">
                    <span>Rider:</span>
                    <strong>{{ $completedOrder->rider->name }} ({{ $completedOrder->rider->vehicle_number }})</strong>
                  </div>
                @endif
                @if ($completedOrder->customer_address)
                  <div class="mt-1 p-1 bg-light rounded" style="font-size: 12px; font-weight: bold;">
                    <strong>Drop-off Address:</strong> {{ $completedOrder->customer_address }}
                  </div>
                @endif
              @endif
              @if ($completedOrder->fbr_invoice_number)
                <div class="d-flex justify-content-between text-truncate" style="font-size: 12px;">
                  <span>FBR Inv:</span>
                  <span>{{ $completedOrder->fbr_invoice_number }}</span>
                </div>
              @endif
            </div>

            <!-- Items Table -->
            <table class="w-100 my-2" style="font-size: 14px; border-collapse: collapse;">
              <thead>
                <tr style="border-bottom: 1.5px dashed #000;">
                  <th class="text-start py-1" style="font-weight: bold;">ITEM</th>
                  <th class="text-center py-1" style="width: 38px; font-weight: bold;">QTY</th>
                  <th class="text-end py-1" style="width: 55px; font-weight: bold;">PRICE</th>
                  <th class="text-end py-1" style="width: 65px; font-weight: bold;">TOTAL</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($completedOrder->items as $i)
                  <tr style="border-bottom: 1px dashed #ccc;">
                    <td class="text-start py-1.5">
                      <div class="fw-bold" style="font-size: 15px;">{{ $i->product_name }}</div>
                      @php
                        $urduName = $i->product_name_ur ?? $i->product?->name_ur;
                      @endphp
                      @if ($urduName)
                        <div style="font-family: 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq', 'Urdu Typesetting', Tahoma, sans-serif; font-size: 15px; font-weight: bold; direction: rtl; text-align: right; line-height: 1.4;">{{ $urduName }}</div>
                      @endif
                      @php
                        $dealModel = $i->deal ?? \App\Models\Deal::where('product_id', $i->product_id)->with('items.product')->first();
                      @endphp
                      @if ($dealModel && $dealModel->items->isNotEmpty())
                        <div style="font-size: 12px; font-weight: bold; color: #333; margin-top: 2px;">
                          @foreach ($dealModel->items as $dItem)
                            <div>↳ {{ (int)($dItem->quantity * $i->quantity) }}x {{ $dItem->product?->name }}</div>
                          @endforeach
                        </div>
                      @endif
                      @if ($i->notes)
                        <div style="font-size: 11px; font-weight: bold; background: #000; color: #fff; padding: 1px 4px; display: inline-block; margin-top: 2px;">* {{ $i->notes }}</div>
                      @endif
                    </td>
                    <td class="text-center py-1.5 fw-bold" style="font-size: 16px; vertical-align: top;">{{ (int)$i->quantity }}</td>
                    <td class="text-end py-1.5" style="font-size: 14px; vertical-align: top;">{{ number_format($i->unit_price) }}</td>
                    <td class="text-end py-1.5 fw-bold" style="font-size: 15px; vertical-align: top;">{{ number_format($i->subtotal) }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>

            <!-- Financial Totals -->
            <div class="mb-3" style="font-size: 14px;">
              <div class="d-flex justify-content-between">
                <span>Subtotal:</span>
                <span class="fw-bold">Rs. {{ number_format($completedOrder->subtotal, 2) }}</span>
              </div>
              @if ($completedOrder->discount_amount > 0)
                <div class="d-flex justify-content-between">
                  <span>Discount:</span>
                  <span class="fw-bold text-danger">- Rs. {{ number_format($completedOrder->discount_amount, 2) }}</span>
                </div>
              @endif
              @if ($completedOrder->delivery_charge > 0)
                <div class="d-flex justify-content-between">
                  <span>Delivery Charge:</span>
                  <span class="fw-bold">+ Rs. {{ number_format($completedOrder->delivery_charge, 2) }}</span>
                </div>
              @endif
              @if ($completedOrder->tax_amount > 0)
                <div class="d-flex justify-content-between">
                  <span>Tax:</span>
                  <span class="fw-bold">+ Rs. {{ number_format($completedOrder->tax_amount, 2) }}</span>
                </div>
              @endif
              <div class="d-flex justify-content-between fw-bold py-1 my-1" style="font-size: 19px; border-top: 2px dashed #000; border-bottom: 2px dashed #000;">
                <span>TOTAL:</span>
                <span>Rs. {{ number_format($completedOrder->grand_total, 2) }}</span>
              </div>
              <div class="d-flex justify-content-between" style="font-size: 14px;">
                <span>Amount Paid:</span>
                <span class="fw-bold">Rs. {{ number_format($completedOrder->paid_amount, 2) }}</span>
              </div>
              @if ($completedOrder->balance_amount > 0)
                <div class="d-flex justify-content-between text-danger fw-bold" style="font-size: 16px;">
                  <span>Balance Due:</span>
                  <span>Rs. {{ number_format($completedOrder->balance_amount, 2) }}</span>
                </div>
              @endif
            </div>

            <!-- Footer -->
            <div class="text-center pt-2" style="font-size: 12px; font-weight: bold; border-top: 1.5px dashed #000;">
              <div style="font-size: 14px;">*** THANK YOU! ***</div>
              <div style="margin-top: 3px;">{{ \App\Models\SystemSetting::get('invoice_footer_note', 'Please visit again!') }}</div>
            </div>
          </div>
          <div class="modal-footer no-print d-flex flex-column gap-2 p-2">
            <!-- WhatsApp Receipt Dispatch Section -->
            <div class="w-100 p-2 bg-light rounded border">
              <div class="d-flex align-items-center justify-content-between mb-1">
                <span class="small fw-bold text-success d-flex align-items-center gap-1">
                  <i class="bi bi-whatsapp"></i> Customer WhatsApp Receipt
                </span>
                @if ($whatsAppFallbackUrl)
                  <a href="{{ $whatsAppFallbackUrl }}" target="_blank" class="small text-decoration-none text-success fw-semibold">
                    <i class="bi bi-box-arrow-up-right me-1"></i> Open via Web
                  </a>
                @endif
              </div>
              <div class="input-group input-group-sm">
                <span class="input-group-text bg-white text-muted"><i class="bi bi-telephone"></i></span>
                <input type="text" class="form-control" placeholder="Customer WhatsApp (e.g. 03001234567)" wire:model.defer="whatsAppRecipientPhone">
                <button type="button" class="btn btn-success d-flex align-items-center gap-1" wire:click="sendWhatsAppReceipt" wire:loading.attr="disabled" wire:target="sendWhatsAppReceipt">
                  <span wire:loading.remove wire:target="sendWhatsAppReceipt">
                    <i class="bi bi-send-fill me-1"></i> Send
                  </span>
                  <span wire:loading wire:target="sendWhatsAppReceipt">
                    <span class="spinner-border spinner-border-sm"></span> Sending...
                  </span>
                </button>
              </div>
            </div>

            <!-- Modal Action Buttons -->
            <div class="w-100 d-flex justify-content-between align-items-center mt-1">
              <button type="button" class="btn btn-secondary btn-sm" wire:click="closeReceiptModal">Close</button>
              <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-success btn-sm" wire:click="openWhatsAppConnectModal" title="Check WhatsApp Connection / QR">
                  <i class="bi bi-qr-code-scan me-1"></i> WhatsApp Status
                </button>
                <button type="button" class="btn btn-primary btn-sm" onclick="window.print()">
                  <i class="bi bi-printer me-1"></i> Print Receipt
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  @endif

  <!-- OPEN ORDERS MODAL -->
  @if ($showOpenOrdersModal)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.6);" wire:click.self="$set('showOpenOrdersModal', false)">
      <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
          <div class="modal-header d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
              <h5 class="modal-title fw-bold mb-0"><i class="bi bi-clock-history me-2 text-primary"></i> Open Active Orders</h5>
              <span class="badge bg-primary px-2 py-1 fs-6">{{ $orderType }}</span>
            </div>
            <!-- Type Tabs -->
            <div class="btn-group btn-group-sm ms-auto me-3" role="group">
              <button type="button" wire:click="setOrderType('TAKEAWAY')" class="btn {{ $orderType === 'TAKEAWAY' ? 'btn-primary' : 'btn-outline-secondary' }}">
                <i class="ph-duotone ph-shopping-bag me-1"></i> Takeaway
              </button>
              <button type="button" wire:click="setOrderType('DINE_IN')" class="btn {{ $orderType === 'DINE_IN' ? 'btn-primary' : 'btn-outline-secondary' }}">
                <i class="ph-duotone ph-fork-knife me-1"></i> Dine-In
              </button>
              <button type="button" wire:click="setOrderType('DELIVERY')" class="btn {{ $orderType === 'DELIVERY' ? 'btn-primary' : 'btn-outline-secondary' }}">
                <i class="ph-duotone ph-moped me-1"></i> Delivery
              </button>
            </div>
            <button type="button" class="btn-close" wire:click="$set('showOpenOrdersModal', false)"></button>
          </div>
          <div class="modal-body p-0">
            @php
              $openOrders = \App\Models\Order::with(['items', 'table', 'kots'])
                ->whereNull('finalized_at')
                ->where('order_status', '!=', 'cancelled')
                ->where('order_type', $orderType)
                ->latest()
                ->get();
            @endphp
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Order #</th>
                    <th>Type</th>
                    <th>Table / Customer</th>
                    <th>Items</th>
                    <th style="min-width: 145px;">KOT Status</th>
                    <th class="text-end">Total</th>
                    <th class="text-center" style="min-width: 160px;">Action</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse ($openOrders as $o)
                    <tr>
                      <td class="fw-bold">
                        {{ $o->order_number }}
                        @if ($o->kots && $o->kots->count() > 1)
                          <span class="badge bg-danger ms-1" style="font-size: 0.65rem;" title="Order has additional recalled items">
                            {{ $o->kots->count() }} KOTs
                          </span>
                        @endif
                      </td>
                      <td><span class="badge bg-secondary">{{ $o->order_type }}</span></td>
                      <td>
                        <div class="fw-semibold">{{ $o->table_name ?: ($o->customer_name ?: 'Walk-in') }}</div>
                        @if ($o->customer_phone)
                          <div class="small text-muted" style="font-size: 0.72rem;"><i class="bi bi-telephone me-1"></i>{{ $o->customer_phone }}</div>
                        @endif
                      </td>
                      <td>{{ $o->items->count() }} items</td>
                      <td>
                        @php
                          $currentStatus = $o->kot_status ?: 'prep';
                          $kotClass = match($currentStatus) {
                            'marination' => 'border-warning text-warning-emphasis',
                            'baking' => 'border-info text-info-emphasis',
                            'packing' => 'border-primary text-primary-emphasis',
                            'ready' => 'border-success text-success-emphasis',
                            default => 'border-secondary text-secondary-emphasis'
                          };
                        @endphp
                        <select 
                          wire:change="updateOrderKotStatus({{ $o->id }}, $event.target.value)" 
                          class="form-select form-select-sm fw-bold py-1 px-2 text-uppercase {{ $kotClass }}" 
                          style="font-size: 0.72rem; height: 28px;">
                          <option value="prep" {{ $currentStatus === 'prep' ? 'selected' : '' }}>1. Prep (تیار)</option>
                          <option value="marination" {{ $currentStatus === 'marination' ? 'selected' : '' }}>2. Marination (میرینیشن)</option>
                          <option value="baking" {{ $currentStatus === 'baking' ? 'selected' : '' }}>3. Baking (بییکنگ)</option>
                          <option value="packing" {{ $currentStatus === 'packing' ? 'selected' : '' }}>4. Packing (پیکنگ)</option>
                          <option value="ready" {{ $currentStatus === 'ready' ? 'selected' : '' }}>5. Ready (تیار شدہ)</option>
                        </select>
                      </td>
                      <td class="text-end fw-bold">Rs. {{ number_format($o->grand_total) }}</td>
                      <td class="text-center">
                        <div class="d-inline-flex gap-1">
                          <button type="button" wire:click="loadExistingOrder({{ $o->id }})" class="btn btn-sm btn-primary" title="Recall & Add More Items">
                            <i class="bi bi-folder2-open me-1"></i> Recall
                          </button>
                          <button type="button" wire:click="cancelOpenOrder({{ $o->id }})" wire:confirm="Are you sure you want to CANCEL Open Order #{{ $o->order_number }}?" class="btn btn-sm btn-outline-danger" title="Cancel this open order">
                            <i class="bi bi-x-circle me-1"></i> Cancel
                          </button>
                        </div>
                      </td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="7" class="text-center py-4 text-muted">No open {{ strtolower($orderType) }} orders currently pending.</td>
                    </tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
  @endif

  <!-- DELIVERY DETAILS SETUP MODAL (Keeps the POS cart items completely unobstructed) -->
  @if ($showDeliveryModal)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.65); z-index: 1060;" aria-modal="true" role="dialog" wire:click.self="$set('showDeliveryModal', false)">
      <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
        <div class="modal-content border-0 shadow">
          <div class="modal-header bg-primary text-white py-2 px-3">
            <h6 class="modal-title fw-bold mb-0">
              <i class="ph-duotone ph-moped me-2 fs-5"></i> Delivery Details ({{ $orderNumber }})
            </h6>
            <button type="button" class="btn-close btn-close-white" wire:click="$set('showDeliveryModal', false)"></button>
          </div>
          <div class="modal-body p-3">
            <div class="row g-3">
              <!-- Delivery Area -->
              <div class="col-sm-7">
                <label class="form-label small fw-semibold text-muted mb-1">Delivery Area / Sector</label>
                <select wire:model.live="deliveryAreaId" class="form-select form-select-sm">
                  <option value="">-- Select Delivery Area --</option>
                  @foreach ($deliveryAreas as $area)
                    <option value="{{ $area->id }}">{{ $area->name }} (Rs. {{ number_format($area->delivery_charge) }})</option>
                  @endforeach
                </select>
              </div>
              <!-- Delivery Fee (Auto-filled & Editable) -->
              <div class="col-sm-5">
                <label class="form-label small fw-semibold text-muted mb-1">Delivery Fee (Rs.)</label>
                <div class="input-group input-group-sm">
                  <span class="input-group-text">Rs.</span>
                  <input type="number" step="1" min="0" wire:model.live.debounce.300ms="deliveryCharge" class="form-control form-control-sm text-end fw-bold">
                </div>
              </div>
              <!-- Delivery Rider -->
              <div class="col-12">
                <label class="form-label small fw-semibold text-muted mb-1">Assign Delivery Rider</label>
                <select wire:model="deliveryRiderId" class="form-select form-select-sm">
                  <option value="">-- Choose Rider --</option>
                  @foreach ($riders as $rider)
                    <option value="{{ $rider->id }}">{{ $rider->name }} ({{ $rider->vehicle_type ?? 'Bike' }} - {{ $rider->vehicle_number ?? 'No Plate' }})</option>
                  @endforeach
                </select>
              </div>
              <!-- Drop-off Address -->
              <div class="col-12">
                <label class="form-label small fw-semibold text-muted mb-1">Drop-off Street Address / House / Landmark</label>
                <textarea wire:model.defer="customerAddress" class="form-control form-control-sm" rows="2" placeholder="e.g. House 42, Street 8, Block B, near Main Market..."></textarea>
              </div>
            </div>
          </div>
          <div class="modal-footer py-2 px-3 bg-light d-flex justify-content-between">
            <span class="small text-muted">Fee updates order total automatically</span>
            <button type="button" class="btn btn-primary btn-sm px-4 fw-semibold" wire:click="$set('showDeliveryModal', false)">
              <i class="bi bi-check2 me-1"></i> Done & Continue
            </button>
          </div>
        </div>
      </div>
    </div>
  @endif

  <!-- OPEN SHIFT MODAL -->
  @if ($showOpenShiftModal)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.65); z-index: 1070;" aria-modal="true" role="dialog" wire:click.self="$set('showOpenShiftModal', false)">
      <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <div class="modal-content border-0 shadow">
          <div class="modal-header bg-dark text-white py-2 px-3">
            <h6 class="modal-title fw-bold mb-0">
              <i class="ph-duotone ph-vault me-2 text-warning fs-5"></i> Open Cash Drawer Shift
            </h6>
            <button type="button" class="btn-close btn-close-white" wire:click="$set('showOpenShiftModal', false)"></button>
          </div>
          <div class="modal-body p-4">
            <p class="small text-muted mb-3">
              A cash shift must be opened before punching or taking orders. Enter your starting cash float.
            </p>

            <div class="mb-3">
              <label class="form-label small fw-semibold text-muted mb-1">Opening Cash Float (Rs.) <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text fw-bold">Rs.</span>
                <input type="number" step="100" min="0" wire:model.defer="posOpeningCash" class="form-control form-control-lg fw-bold text-end text-success" autofocus>
              </div>
              <div class="d-flex gap-2 mt-2">
                <button type="button" wire:click="$set('posOpeningCash', 2000)" class="btn btn-outline-secondary btn-sm py-0 px-2 small">Rs. 2,000</button>
                <button type="button" wire:click="$set('posOpeningCash', 5000)" class="btn btn-outline-secondary btn-sm py-0 px-2 small">Rs. 5,000</button>
                <button type="button" wire:click="$set('posOpeningCash', 10000)" class="btn btn-outline-secondary btn-sm py-0 px-2 small">Rs. 10,000</button>
                <button type="button" wire:click="$set('posOpeningCash', 0)" class="btn btn-outline-secondary btn-sm py-0 px-2 small">Rs. 0</button>
              </div>
            </div>

            <div class="mb-2">
              <label class="form-label small fw-semibold text-muted mb-1">Shift Notes (Optional)</label>
              <input type="text" wire:model.defer="posShiftNotes" class="form-control form-control-sm" placeholder="e.g. Lunch / Evening Shift, Float from safe">
            </div>
          </div>
          <div class="modal-footer py-2 px-3 bg-light d-flex justify-content-between">
            <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="$set('showOpenShiftModal', false)">Cancel</button>
            <button type="button" class="btn btn-success btn-sm px-4 fw-bold" wire:click="openShiftFromPos">
              <i class="bi bi-key-fill me-1"></i> Open Shift & Start
            </button>
          </div>
        </div>
      </div>
    </div>
  @endif

  <!-- SIZE / VARIATION SELECTION MODAL -->
  @if ($showVariantModal && $selectedParentProduct)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.65); z-index: 1065;" aria-modal="true" role="dialog" wire:click.self="closeVariantModal">
      <div class="modal-dialog modal-dialog-centered" style="max-width: 500px;">
        <div class="modal-content border-0 shadow-lg">
          <div class="modal-header bg-primary text-white py-3 px-3">
            <div>
              <h6 class="modal-title fw-bold mb-0">
                <i class="bi bi-layers-fill me-2 fs-5"></i> Select Size / Variation
              </h6>
              <div class="text-white-50 small mt-1">
                {{ $selectedParentProduct->name }}
              </div>
            </div>
            <button type="button" class="btn-close btn-close-white" wire:click="closeVariantModal"></button>
          </div>
          <div class="modal-body p-3">
            <p class="small text-muted mb-3">
              Tap a size below to add it directly to the order:
            </p>
            <div class="row g-2">
              @foreach ($selectedParentProduct->variants->where('is_active', true) as $variant)
                <div class="col-12 col-sm-6">
                  <button type="button" 
                    wire:click="selectVariantAndAddToCart({{ $variant->id }})" 
                    class="card w-100 p-3 text-start border border-2 border-primary-subtle bg-surface transition-base d-flex flex-column justify-content-between h-100"
                    style="cursor: pointer; min-height: 84px;"
                    onmouseover="this.classList.add('border-primary', 'shadow-sm')"
                    onmouseout="this.classList.remove('border-primary', 'shadow-sm')">
                    <div class="d-flex justify-content-between align-items-center w-100 mb-2">
                      <span class="fw-bold text-heading fs-6">{{ $variant->variation_name ?: $variant->name }}</span>
                      <span class="badge bg-light text-muted border px-2 py-1" style="font-size: 0.7rem;">
                        {{ $variant->code }}
                      </span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center w-100">
                      <span class="fw-bold fs-5 text-primary">Rs. {{ number_format($variant->sale_price) }}</span>
                      <span class="badge bg-primary rounded-pill px-2 py-1 small">
                        <i class="bi bi-plus-lg me-1"></i> Add
                      </span>
                    </div>
                  </button>
                </div>
              @endforeach
            </div>
          </div>
          <div class="modal-footer py-2 px-3 bg-light d-flex justify-content-between">
            <span class="small text-muted"><kbd class="bg-white text-dark border">Esc</kbd> to Cancel</span>
            <button type="button" class="btn btn-outline-secondary btn-sm px-3" wire:click="closeVariantModal">
              Cancel
            </button>
          </div>
        </div>
      </div>
    </div>
  @endif

  <!-- OFFLINE CHECKOUT MODAL (For instant 100% offline payment & finalization) -->
  <div class="modal fade" id="posOfflineCheckoutModal" tabindex="-1" aria-hidden="true" style="display: none; background: rgba(0,0,0,0.6);">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border shadow-lg">
        <div class="modal-header bg-primary text-white py-2">
          <h5 class="modal-title fs-6 fw-bold">
            <i class="ph-duotone ph-wifi-slash me-1"></i> Offline Checkout & Cash Out
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-3">
          <div class="alert alert-warning py-1 px-2 small mb-3" style="font-size: 0.75rem;">
            <i class="bi bi-info-circle me-1"></i> Running in <strong>Offline Mode</strong>. Order & receipt will save directly to local IndexedDB and sync to cloud once connection is restored.
          </div>
          <div class="d-flex justify-content-between align-items-center mb-3 p-2 bg-light rounded">
            <span class="fw-bold">Payable Total:</span>
            <span class="fs-4 fw-bold text-primary" id="posOfflinePayableAmount">Rs. 0.00</span>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Payment Method</label>
            <div class="btn-group w-100" role="group">
              <input type="radio" class="btn-check" name="offlinePayMethod" id="offlinePayCash" value="cash" checked autocomplete="off">
              <label class="btn btn-outline-primary" for="offlinePayCash"><i class="ph-duotone ph-money me-1"></i> Cash</label>

              <input type="radio" class="btn-check" name="offlinePayMethod" id="offlinePayCard" value="card" autocomplete="off">
              <label class="btn btn-outline-primary" for="offlinePayCard"><i class="ph-duotone ph-credit-card me-1"></i> Card</label>
            </div>
          </div>

          <div class="mb-3" id="posOfflineCashTenderGroup">
            <label class="form-label small fw-semibold">Amount Tendered</label>
            <input type="number" id="posOfflineTenderedInput" class="form-control form-control-lg text-end fw-bold" step="0.01">
            <div class="d-flex flex-wrap gap-1 mt-2">
              <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.setOfflineQuickTender('exact')">Exact</button>
              <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.setOfflineQuickTender(500)">500</button>
              <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.setOfflineQuickTender(1000)">1,000</button>
              <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.setOfflineQuickTender(2000)">2,000</button>
              <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.setOfflineQuickTender(5000)">5,000</button>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-2 p-2 bg-success-subtle text-success rounded fw-bold">
              <span>Change Due:</span>
              <span id="posOfflineChangeDue">Rs. 0.00</span>
            </div>
          </div>
        </div>
        <div class="modal-footer py-2 bg-light">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary btn-sm px-4 fw-bold" onclick="window.completeOfflineCheckout()">
            <i class="ph-duotone ph-check-circle me-1"></i> CONFIRM & CASH OUT
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- WHATSAPP CONNECTION & QR PAIRING MODAL -->
  @if ($showWhatsAppModal)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.75);" wire:click.self="closeWhatsAppModal" wire:poll.2500ms="checkWhatsAppConnection">
      <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <div class="modal-content border-0 shadow">
          <div class="modal-header py-2 bg-success text-white">
            <h6 class="modal-title fw-bold mb-0 d-flex align-items-center gap-2">
              <i class="bi bi-whatsapp"></i> WhatsApp Device Connection
            </h6>
            <button type="button" class="btn-close btn-close-white" wire:click="closeWhatsAppModal"></button>
          </div>
          <div class="modal-body p-4 text-center">
            @if ($whatsAppConnected)
              <div class="py-3">
                <div class="d-inline-flex align-items-center justify-content-center bg-success bg-opacity-10 text-success rounded-circle mb-3" style="width: 70px; height: 70px;">
                  <i class="bi bi-check-circle-fill fs-1"></i>
                </div>
                <h5 class="fw-bold text-success mb-1">WhatsApp Connected!</h5>
                <p class="text-muted small mb-3">Your device is linked and ready to send digital receipts directly to customers.</p>
                @if ($completedOrder)
                  <div class="mt-3 p-3 bg-light rounded border text-start">
                    <label class="form-label small fw-bold mb-1">Send receipt for Order #{{ $completedOrder->order_number }}:</label>
                    <div class="input-group input-group-sm">
                      <input type="text" class="form-control" placeholder="Customer WhatsApp Phone" wire:model.defer="whatsAppRecipientPhone">
                      <button type="button" class="btn btn-success" wire:click="sendWhatsAppReceipt" wire:loading.attr="disabled" wire:target="sendWhatsAppReceipt">
                        <span wire:loading.remove wire:target="sendWhatsAppReceipt"><i class="bi bi-send-fill me-1"></i> Send</span>
                        <span wire:loading wire:target="sendWhatsAppReceipt"><span class="spinner-border spinner-border-sm"></span> Sending...</span>
                      </button>
                    </div>
                  </div>
                @endif
              </div>
            @elseif ($whatsAppQrCode)
              <p class="small text-muted mb-2">Scan this QR code with WhatsApp on your phone to link your restaurant account:</p>
              <div class="p-2 bg-white border rounded d-inline-block shadow-sm mb-3">
                <img src="{{ $whatsAppQrCode }}" alt="WhatsApp QR Code" class="img-fluid" style="max-width: 250px; height: auto;">
              </div>
              <div class="text-start bg-light rounded p-2.5 small border" style="font-size: 12px;">
                <strong>How to scan:</strong>
                <ol class="mb-0 ps-3 mt-1 text-secondary">
                  <li>Open <strong>WhatsApp</strong> on your phone</li>
                  <li>Go to <strong>Linked Devices</strong> &rarr; <strong>Link a Device</strong></li>
                  <li>Point camera at this QR code</li>
                </ol>
              </div>
              <div class="small text-muted mt-2">
                <span class="spinner-border spinner-border-sm text-success me-1"></span> Waiting for scan...
              </div>
            @else
              <div class="py-4">
                <div class="spinner-border text-success mb-3" role="status"></div>
                <h6>Connecting to WhatsApp Bridge...</h6>
                <p class="text-muted small mb-2">Checking service on port 3333...</p>
                <div class="alert alert-light border small text-muted mt-3 mb-0 text-start">
                  If service is not running, run in terminal: <br>
                  <code class="user-select-all">php artisan whatsapp:serve</code>
                </div>
              </div>
            @endif
          </div>
          <div class="modal-footer py-2">
            <button type="button" class="btn btn-secondary btn-sm" wire:click="closeWhatsAppModal">Close</button>
            <button type="button" class="btn btn-outline-success btn-sm" wire:click="openWhatsAppConnectModal">
              <i class="bi bi-arrow-clockwise me-1"></i> Refresh QR
            </button>
          </div>
        </div>
      </div>
    </div>
  @endif
</div>

<style>
  .pos-action-btn:focus, #posSaveOrderBtn:focus {
    outline: 3px solid #0d6efd !important;
    outline-offset: 2px !important;
    box-shadow: 0 0 12px rgba(13, 110, 253, 0.6) !important;
    background-color: #0d6efd !important;
    color: #ffffff !important;
    transform: translateY(-1px);
    transition: all 0.15s ease-in-out;
  }
  .pos-action-btn:focus i, .pos-action-btn:focus span {
    color: #ffffff !important;
  }
  #posProductCodeInput:focus {
    border-color: #0d6efd !important;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25) !important;
  }
</style>

<script>
  window.posOfflineCart = [];
  window.posOfflineTableId = null;
  window.posOfflineTableName = '';
  window.posOfflineOrderType = '{{ $orderType }}';

  document.addEventListener('livewire:initialized', () => {
    let activePrintPopup = null;

    // 1. Livewire Network Error Interceptor (Prevents crash modals when internet drops)
    if (typeof Livewire.hook !== 'undefined') {
      Livewire.hook('request', ({ uri, options, payload, respond, succeed, fail }) => {
        fail(({ status, preventDefault }) => {
          if (status === 0 || !navigator.onLine || (window.PosOfflineEngine && !window.PosOfflineEngine.isOnline())) {
            preventDefault();
            console.warn('Livewire network request dropped while offline. Switched to POS Offline Mode.');
            if (window.PosOfflineEngine) {
              window.PosOfflineEngine.handleConnectivityChange(false);
            }
          }
        });
      });
    }

    // 2. Thermal Printing listeners
    Livewire.on('print-bill', (event) => {
      const url = event.url || (event[0] && event[0].url);
      if (url) {
        activePrintPopup = window.open(url, '_blank', 'width=420,height=650,location=no,toolbar=no');
        if (activePrintPopup) activePrintPopup.focus();
      }
    });

    Livewire.on('print-kot', (event) => {
      const url = event.url || (event[0] && event[0].url);
      if (url) {
        activePrintPopup = window.open(url, '_blank', 'width=420,height=600,location=no,toolbar=no');
        if (activePrintPopup) activePrintPopup.focus();
      }
    });

    // 3. Global ESC key listener to cancel modals & close print popups
    window.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        if (activePrintPopup && !activePrintPopup.closed) {
          try {
            activePrintPopup.close();
            activePrintPopup = null;
          } catch (err) {}
        }
        const offlineModal = document.getElementById('posOfflineCheckoutModal');
        if (offlineModal && offlineModal.classList.contains('show')) {
          const bs = bootstrap.Modal.getInstance(offlineModal);
          if (bs) bs.hide();
        }
        if (typeof @this !== 'undefined') {
          @this.call('closeAnyOpenModal');
        }
      }
    });

    // 4. Focus Handlers
    Livewire.on('focus-save-button', () => {
      setTimeout(() => {
        const btn = document.getElementById('posSaveOrderBtn');
        if (btn) {
          btn.focus();
          btn.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
      }, 50);
    });

    Livewire.on('refocus-product-code', () => {
      setTimeout(() => {
        const inp = document.getElementById('posProductCodeInput');
        if (inp) {
          inp.value = '';
          inp.focus();
        }
      }, 50);
    });

    Livewire.on('select-product-code', () => {
      setTimeout(() => {
        const inp = document.getElementById('posProductCodeInput');
        if (inp) {
          inp.focus();
          inp.select();
        }
      }, 50);
    });

    Livewire.on('refocus-pos-inputs', () => {
      setTimeout(() => {
        const codeInput = document.getElementById('posProductCodeInput');
        if (codeInput) {
          codeInput.focus();
        } else {
          const phoneInput = document.getElementById('posCustomerPhone');
          if (phoneInput) phoneInput.focus();
        }
      }, 50);
    });

    Livewire.on('focus-customer-name', () => {
      setTimeout(() => {
        const inp = document.getElementById('posCustomerName');
        if (inp) {
          inp.focus();
          inp.select();
        }
      }, 50);
    });

    Livewire.on('focus-customer-phone', () => {
      setTimeout(() => {
        const inp = document.getElementById('posCustomerPhone');
        if (inp) {
          inp.focus();
          inp.select();
        }
      }, 50);
    });

    Livewire.on('order-saved-reset', () => {
      setTimeout(() => {
        const phoneInput = document.getElementById('posCustomerPhone');
        if (phoneInput) {
          phoneInput.focus();
        } else {
          const codeInput = document.getElementById('posProductCodeInput');
          if (codeInput) codeInput.focus();
        }
      }, 100);
    });

    // 5. OFFLINE ORDER HANDLING & EVENT INTERCEPTION
    window.getPosOrderState = function() {
      let cart = [];
      let orderType = 'TAKEAWAY';
      let tableId = null;
      let tableName = '';
      let customerName = '';
      let customerPhone = '';
      let customerAddress = '';
      let orderNotes = '';
      let discountType = 'fixed';
      let discountRate = 0;
      let deliveryCharge = 0;
      let taxRate = 0;

      if (typeof @this !== 'undefined') {
        try {
          cart = @this.get('cart') || [];
          orderType = @this.get('orderType') || 'TAKEAWAY';
          tableId = @this.get('selectedTableId') || null;
          tableName = @this.get('selectedTableName') || '';
          customerName = @this.get('customerName') || '';
          customerPhone = @this.get('customerPhone') || '';
          customerAddress = @this.get('customerAddress') || '';
          orderNotes = @this.get('orderNotes') || '';
          discountType = @this.get('discountType') || 'fixed';
          discountRate = parseFloat(@this.get('discountRate') || 0);
          deliveryCharge = parseFloat(@this.get('deliveryCharge') || 0);
          taxRate = parseFloat(@this.get('taxRate') || 0);
        } catch(err) {}
      }

      const nameInput = document.getElementById('posCustomerName');
      if (nameInput && nameInput.value) customerName = nameInput.value.trim();

      const phoneInput = document.getElementById('posCustomerPhone');
      if (phoneInput && phoneInput.value) customerPhone = phoneInput.value.trim();

      const addrInput = document.getElementById('posCustomerAddress');
      if (addrInput && addrInput.value) customerAddress = addrInput.value.trim();

      if (window.posOfflineCart && window.posOfflineCart.length > 0) {
        cart = window.posOfflineCart;
      }
      if (window.posOfflineOrderType) {
        orderType = window.posOfflineOrderType;
      }
      if (window.posOfflineTableId) {
        tableId = window.posOfflineTableId;
        tableName = window.posOfflineTableName || '';
      }

      let subtotal = 0;
      cart.forEach(item => {
        subtotal += (parseFloat(item.price || 0) * parseFloat(item.qty || 1));
      });

      let discountAmount = 0;
      if (discountType === 'percent') {
        discountAmount = (subtotal * discountRate) / 100.0;
      } else {
        discountAmount = Math.min(subtotal, discountRate);
      }

      let taxAmount = 0;
      if (taxRate > 0) {
        taxAmount = ((subtotal - discountAmount) * taxRate) / 100.0;
      }

      const grandTotal = Math.max(0, subtotal - discountAmount + taxAmount + deliveryCharge);

      return {
        cart, orderType, tableId, tableName, customerName, customerPhone,
        customerAddress, orderNotes, discountType, discountRate, discountAmount,
        deliveryCharge, taxRate, taxAmount, subtotal, grandTotal
      };
    };

    window.validateOfflineOrder = function(state) {
      if (!state.cart || state.cart.length === 0) {
        PosOfflineEngine.showToast('Cart is empty. Please add products first.', 'danger');
        return false;
      }
      if (state.orderType === 'DINE_IN' && !state.tableId) {
        PosOfflineEngine.showToast('Table selection is mandatory for Dine-In orders. Please select a table.', 'danger');
        return false;
      }
      if (state.orderType === 'DELIVERY') {
        if (!state.customerName || state.customerName.toLowerCase() === 'walk-in customer' || state.customerName.toLowerCase() === 'walk-in') {
          PosOfflineEngine.showToast('Customer Name is mandatory for Delivery orders.', 'danger');
          document.getElementById('posCustomerName')?.focus();
          return false;
        }
        const cleanPhone = (state.customerPhone || '').replace(/[^0-9]/g, '');
        if (!cleanPhone || cleanPhone.length < 10) {
          PosOfflineEngine.showToast('Customer Phone Number (at least 10 digits e.g. 03001234567) is mandatory for Delivery.', 'danger');
          document.getElementById('posCustomerPhone')?.focus();
          return false;
        }
      }
      return true;
    };

    window.executeOfflineAction = async function(action) {
      const state = window.getPosOrderState();
      if (!window.validateOfflineOrder(state)) {
        return;
      }

      if (action === 'pay') {
        const modalEl = document.getElementById('posOfflineCheckoutModal');
        document.getElementById('posOfflinePayableAmount').innerText = 'Rs. ' + state.grandTotal.toFixed(2);
        const tenderedInput = document.getElementById('posOfflineTenderedInput');
        tenderedInput.value = state.grandTotal.toFixed(2);
        document.getElementById('posOfflineChangeDue').innerText = 'Rs. 0.00';

        tenderedInput.oninput = () => {
          const tendered = parseFloat(tenderedInput.value || 0);
          const change = Math.max(0, tendered - state.grandTotal);
          document.getElementById('posOfflineChangeDue').innerText = 'Rs. ' + change.toFixed(2);
        };

        const bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
        bsModal.show();
        return;
      }

      const offlineNum = await PosOfflineDB.getNextOfflineOrderNumber(state.orderType);
      const settings = (await PosOfflineDB.getCatalogItem('settings')) || {
        restaurant_name: 'Food Point POS',
        currency: 'Rs.'
      };

      const orderData = {
        client_uuid: PosOfflineDB.generateUUID(),
        offline_order_number: offlineNum,
        order_type: state.orderType,
        table_id: state.tableId,
        table_name: state.tableName,
        customer_name: state.customerName,
        customer_phone: state.customerPhone,
        customer_address: state.customerAddress,
        order_notes: state.orderNotes,
        discount_type: state.discountType,
        discount_rate: state.discountRate,
        discount_amount: state.discountAmount,
        delivery_charge: state.deliveryCharge,
        subtotal: state.subtotal,
        tax_amount: state.taxAmount,
        grand_total: state.grandTotal,
        is_finalized: 0,
        payment_status: 'unpaid',
        payment_method: 'cash',
        paid_amount: 0,
        created_at: new Date().toISOString(),
        items: state.cart.map(i => ({
          product_id: i.product_id,
          name: i.name,
          name_ur: i.name_ur || null,
          sku: i.sku || null,
          price: parseFloat(i.price || 0),
          cost: parseFloat(i.cost || 0),
          quantity: parseFloat(i.qty || 1),
          qty: parseFloat(i.qty || 1),
          notes: i.notes || null,
        }))
      };

      await PosOfflineDB.queueOfflineOrder(orderData);
      PosOfflineEngine.updateQueueBadge();

      if (action === 'save') {
        PosOfflinePrinter.printKOT(orderData, 1);
        PosOfflineEngine.showToast(`Offline Order #${offlineNum} saved to local device! Kitchen KOT printed.`, 'success');
      } else if (action === 'bill') {
        PosOfflinePrinter.printReceipt(orderData, settings);
        PosOfflineEngine.showToast(`Offline Order #${offlineNum} saved to local device! Printing bill...`, 'success');
      }

      window.resetPosAfterOfflineOrder();
    };

    window.completeOfflineCheckout = async function() {
      const state = window.getPosOrderState();
      if (!window.validateOfflineOrder(state)) {
        return;
      }

      const modalEl = document.getElementById('posOfflineCheckoutModal');
      const bsModal = bootstrap.Modal.getInstance(modalEl);
      if (bsModal) bsModal.hide();

      const method = document.querySelector('input[name="offlinePayMethod"]:checked')?.value || 'cash';
      const tendered = parseFloat(document.getElementById('posOfflineTenderedInput')?.value || state.grandTotal);
      const offlineNum = await PosOfflineDB.getNextOfflineOrderNumber(state.orderType);
      const settings = (await PosOfflineDB.getCatalogItem('settings')) || {
        restaurant_name: 'Food Point POS',
        currency: 'Rs.'
      };

      const orderData = {
        client_uuid: PosOfflineDB.generateUUID(),
        offline_order_number: offlineNum,
        order_type: state.orderType,
        table_id: state.tableId,
        table_name: state.tableName,
        customer_name: state.customerName,
        customer_phone: state.customerPhone,
        customer_address: state.customerAddress,
        order_notes: state.orderNotes,
        discount_type: state.discountType,
        discount_rate: state.discountRate,
        discount_amount: state.discountAmount,
        delivery_charge: state.deliveryCharge,
        subtotal: state.subtotal,
        tax_amount: state.taxAmount,
        grand_total: state.grandTotal,
        tendered_amount: tendered,
        is_finalized: 1,
        payment_status: 'paid',
        payment_method: method,
        paid_amount: state.grandTotal,
        created_at: new Date().toISOString(),
        items: state.cart.map(i => ({
          product_id: i.product_id,
          name: i.name,
          name_ur: i.name_ur || null,
          sku: i.sku || null,
          price: parseFloat(i.price || 0),
          cost: parseFloat(i.cost || 0),
          quantity: parseFloat(i.qty || 1),
          qty: parseFloat(i.qty || 1),
          notes: i.notes || null,
        }))
      };

      await PosOfflineDB.queueOfflineOrder(orderData);
      PosOfflineEngine.updateQueueBadge();

      // Thermal Receipt + KOT
      PosOfflinePrinter.printReceipt(orderData, settings);
      PosOfflinePrinter.printKOT(orderData, 1);
      PosOfflineEngine.showToast(`Offline Order #${offlineNum} paid (Rs. ${state.grandTotal.toFixed(0)}) and finalized!`, 'success');

      window.resetPosAfterOfflineOrder();
    };

    window.setOfflineQuickTender = function(val) {
      const state = window.getPosOrderState();
      const tenderedInput = document.getElementById('posOfflineTenderedInput');
      if (!tenderedInput) return;
      const amt = val === 'exact' ? state.grandTotal : parseFloat(val);
      tenderedInput.value = amt.toFixed(2);
      const change = Math.max(0, amt - state.grandTotal);
      document.getElementById('posOfflineChangeDue').innerText = 'Rs. ' + change.toFixed(2);
    };

    window.resetPosAfterOfflineOrder = function() {
      window.posOfflineCart = [];
      window.posOfflineTableId = null;
      window.posOfflineTableName = '';

      const nameInput = document.getElementById('posCustomerName');
      if (nameInput) nameInput.value = '';
      const phoneInput = document.getElementById('posCustomerPhone');
      if (phoneInput) phoneInput.value = '';
      const addrInput = document.getElementById('posCustomerAddress');
      if (addrInput) addrInput.value = '';
      const codeInput = document.getElementById('posProductCodeInput');
      if (codeInput) codeInput.value = '';

      if (typeof @this !== 'undefined') {
        try {
          @this.call('resetNewOrder', @this.get('orderType') || 'TAKEAWAY');
        } catch(e) {}
      }

      const cartContainer = document.getElementById('posCartScrollContainer');
      if (cartContainer) {
        cartContainer.innerHTML = `
          <div class="h-100 d-flex flex-column align-items-center justify-content-center text-muted py-5">
            <i class="ph-duotone ph-shopping-cart fs-1 mb-2 opacity-50"></i>
            <p class="mb-0">Your order cart is empty.</p>
            <small>Tap products from the menu to add items.</small>
          </div>
        `;
      }
      const subtotalEl = document.getElementById('posCartSubtotal');
      if (subtotalEl) subtotalEl.innerText = 'Rs. 0.00';
      const grandTotalEl = document.getElementById('posCartGrandTotal');
      if (grandTotalEl) grandTotalEl.innerText = 'Rs. 0.00';
      const discountEl = document.getElementById('posCartDiscount');
      if (discountEl) discountEl.innerText = '- Rs. 0.00';

      setTimeout(() => {
        document.getElementById('posCustomerPhone')?.focus();
      }, 100);
    };

    window.renderOfflineCart = function() {
      const container = document.getElementById('posCartScrollContainer');
      if (!container) return;
      const items = window.posOfflineCart || [];
      if (items.length === 0) {
        container.innerHTML = `
          <div class="h-100 d-flex flex-column align-items-center justify-content-center text-muted py-5">
            <i class="ph-duotone ph-shopping-cart fs-1 mb-2 opacity-50"></i>
            <p class="mb-0">Your order cart is empty.</p>
            <small>Tap products from the menu or enter item code.</small>
          </div>
        `;
      } else {
        let html = '<div class="list-group list-group-flush">';
        items.forEach((item, index) => {
          const lineTotal = (item.price * item.qty).toFixed(0);
          html += `
            <div class="list-group-item px-0 py-2 border-bottom">
              <div class="d-flex justify-content-between align-items-start mb-1">
                <div class="fw-semibold text-heading small pe-2">${item.name}</div>
                <div class="fw-bold text-heading small">Rs. ${lineTotal}</div>
              </div>
              ${item.notes ? `<div class="small text-warning fst-italic mb-1" style="font-size: 0.75rem;"><i class="bi bi-chat-dots"></i> ${item.notes}</div>` : ''}
              <div class="d-flex justify-content-between align-items-center">
                <div class="small text-muted">Rs. ${item.price} each</div>
                <div class="d-flex align-items-center gap-1">
                  <div class="input-group input-group-sm" style="width: 100px;">
                    <button type="button" onclick="window.offlineUpdateQty(${index}, -1)" class="btn btn-outline-secondary px-2">-</button>
                    <input type="text" readonly value="${item.qty}" class="form-control text-center p-0">
                    <button type="button" onclick="window.offlineUpdateQty(${index}, 1)" class="btn btn-outline-secondary px-2">+</button>
                  </div>
                  <button type="button" onclick="window.offlineRemoveItem(${index})" class="btn btn-sm text-danger p-0 ms-1">
                    <i class="bi bi-x-circle fs-6"></i>
                  </button>
                </div>
              </div>
            </div>
          `;
        });
        html += '</div>';
        container.innerHTML = html;
      }

      const state = window.getPosOrderState();
      const subtotalEl = document.getElementById('posCartSubtotal');
      if (subtotalEl) subtotalEl.innerText = 'Rs. ' + state.subtotal.toFixed(2);
      const grandTotalEl = document.getElementById('posCartGrandTotal');
      if (grandTotalEl) grandTotalEl.innerText = 'Rs. ' + state.grandTotal.toFixed(2);
      const discountEl = document.getElementById('posCartDiscount');
      if (discountEl) discountEl.innerText = '- Rs. ' + state.discountAmount.toFixed(2);

      const disabled = items.length === 0;
      ['posSaveOrderBtn', 'posPrintBillBtn', 'posPayBtn'].forEach(id => {
        const b = document.getElementById(id);
        if (b) b.disabled = disabled;
      });
    };

    window.offlineUpdateQty = function(index, delta) {
      if (window.posOfflineCart && window.posOfflineCart[index]) {
        window.posOfflineCart[index].qty += delta;
        if (window.posOfflineCart[index].qty <= 0) {
          window.posOfflineCart.splice(index, 1);
        }
        window.renderOfflineCart();
      }
    };

    window.offlineRemoveItem = function(index) {
      if (window.posOfflineCart && window.posOfflineCart[index]) {
        window.posOfflineCart.splice(index, 1);
        window.renderOfflineCart();
      }
    };

    // Attach capture-phase interceptors on Save, Print Bill, Pay ONLY when physically offline
    ['posSaveOrderBtn', 'posPrintBillBtn', 'posPayBtn'].forEach(btnId => {
      const btn = document.getElementById(btnId);
      if (btn) {
        btn.addEventListener('click', function(e) {
          if (!navigator.onLine && (!window.PosOfflineEngine || !window.PosOfflineEngine.isOnline())) {
            e.preventDefault();
            e.stopImmediatePropagation();
            const action = btnId === 'posSaveOrderBtn' ? 'save' : (btnId === 'posPrintBillBtn' ? 'bill' : 'pay');
            window.executeOfflineAction(action);
          }
        }, true);
      }
    });

    // Offline item punch via Item Code input ONLY when physically offline
    const codeInp = document.getElementById('posProductCodeInput');
    if (codeInp) {
      codeInp.addEventListener('keydown', async function(e) {
        if (e.key === 'Enter') {
          if (!navigator.onLine && (!window.PosOfflineEngine || !window.PosOfflineEngine.isOnline())) {
            e.preventDefault();
            e.stopImmediatePropagation();
            const val = this.value.trim();
            if (!val) {
              const state = window.getPosOrderState();
              if (state.cart.length > 0) {
                document.getElementById('posSaveOrderBtn')?.focus();
              }
              return;
            }

            let qty = 1.0;
            let code = val;
            if (val.includes('*')) {
              const parts = val.split('*');
              if (!isNaN(parts[0])) {
                qty = Math.max(1, parseFloat(parts[0]));
                code = parts[1].trim();
              } else if (!isNaN(parts[1])) {
                qty = Math.max(1, parseFloat(parts[1]));
                code = parts[0].trim();
              }
            }

            const lower = code.toLowerCase();
            let products = (await PosOfflineDB.getCatalogItem('products')) || window.posInitialProducts || [];
            let found = products.find(p => 
              (p.code && p.code.toLowerCase() === lower) ||
              (p.sku && p.sku.toLowerCase() === lower) ||
              (p.barcode && p.barcode === code) ||
              (String(p.id) === code)
            );

            if (found) {
              if (!window.posOfflineCart || window.posOfflineCart.length === 0) {
                window.posOfflineCart = window.getPosOrderState().cart.slice();
              }
              const existing = window.posOfflineCart.find(i => i.product_id === found.id);
              if (existing) {
                existing.qty += qty;
              } else {
                window.posOfflineCart.push({
                  product_id: found.id,
                  name: found.name,
                  name_ur: found.name_ur || null,
                  sku: found.sku || null,
                  price: parseFloat(found.sale_price || found.price || 0),
                  cost: parseFloat(found.cost_price || found.cost || 0),
                  qty: qty,
                  notes: ''
                });
              }
              this.value = '';
              window.renderOfflineCart();
              PosOfflineEngine.showToast(`Added ${qty}x ${found.name}`, 'info');
            } else {
              PosOfflineEngine.showToast(`Item code "${code}" not found in local catalog.`, 'warning');
            }
          }
        }
      }, true);
    }

    // Capture product card click ONLY when physically offline
    document.addEventListener('click', async function(e) {
      if (!navigator.onLine && (!window.PosOfflineEngine || !window.PosOfflineEngine.isOnline())) {
        const card = e.target.closest('.product-card');
        if (card) {
          e.preventDefault();
          e.stopImmediatePropagation();
          const clickAttr = card.getAttribute('wire:click') || '';
          const match = clickAttr.match(/\((\d+)\)/);
          if (match && match[1]) {
            const prodId = parseInt(match[1]);
            let products = (await PosOfflineDB.getCatalogItem('products')) || window.posInitialProducts || [];
            let found = products.find(p => p.id === prodId);
            if (found) {
              if (!window.posOfflineCart || window.posOfflineCart.length === 0) {
                window.posOfflineCart = window.getPosOrderState().cart.slice();
              }
              const existing = window.posOfflineCart.find(i => i.product_id === found.id);
              if (existing) {
                existing.qty += 1;
              } else {
                window.posOfflineCart.push({
                  product_id: found.id,
                  name: found.name,
                  name_ur: found.name_ur || null,
                  sku: found.sku || null,
                  price: parseFloat(found.sale_price || found.price || 0),
                  cost: parseFloat(found.cost_price || found.cost || 0),
                  qty: 1,
                  notes: ''
                });
              }
              window.renderOfflineCart();
              PosOfflineEngine.showToast(`Added 1x ${found.name}`, 'info');
            }
          }
        }
      }
    }, true);

    // Capture table click ONLY when physically offline
    document.addEventListener('click', function(e) {
      if (!navigator.onLine && (!window.PosOfflineEngine || !window.PosOfflineEngine.isOnline())) {
        const tableBtn = e.target.closest('button');
        if (tableBtn && (tableBtn.getAttribute('wire:click') || '').includes('selectTable')) {
          e.preventDefault();
          e.stopImmediatePropagation();
          const clickAttr = tableBtn.getAttribute('wire:click') || '';
          const match = clickAttr.match(/\((\d+)\)/);
          if (match && match[1]) {
            const tblId = parseInt(match[1]);
            const tblNameEl = tableBtn.querySelector('.text-muted.mt-1');
            const tblName = tblNameEl ? tblNameEl.innerText.trim() : 'Table #' + tblId;
            window.posOfflineTableId = tblId;
            window.posOfflineTableName = tblName;

            const alertEl = document.getElementById('posTableRequiredAlert');
            if (alertEl) alertEl.classList.add('d-none');

            PosOfflineEngine.showToast(`Assigned to ${tblName}`, 'success');
          }
        }
      }
    }, true);
  });
</script>
