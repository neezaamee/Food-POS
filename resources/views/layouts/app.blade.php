<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ $title ?? config('app.name', 'Food Point POS') }}</title>

  <!-- Favicons -->
  <link href="{{ asset('assets/img/favicon.png') }}" rel="icon">
  <link href="{{ asset('assets/img/apple-touch-icon.png') }}" rel="apple-touch-icon">

  <!-- Google Fonts - Inter -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="{{ asset('assets/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
  <link href="{{ asset('assets/vendor/bootstrap-icons/bootstrap-icons.css') }}" rel="stylesheet">
  <link href="{{ asset('assets/vendor/remixicon/remixicon.css') }}" rel="stylesheet">
  <link href="{{ asset('assets/vendor/fontawesome-free/css/all.min.css') }}" rel="stylesheet">
  <link href="{{ asset('assets/vendor/phosphor-icons/phosphor-icons.css') }}" rel="stylesheet">
  <link href="{{ asset('assets/vendor/lucide-icons/lucide.css') }}" rel="stylesheet">
  <link href="{{ asset('assets/vendor/simple-datatables/style.css') }}" rel="stylesheet">
  <link href="{{ asset('assets/vendor/quill/quill.snow.css') }}" rel="stylesheet">
  <link href="{{ asset('assets/vendor/quill/quill.bubble.css') }}" rel="stylesheet">
  <link href="{{ asset('assets/vendor/choices.js/choices.min.css') }}" rel="stylesheet">
  <link href="{{ asset('assets/vendor/flatpickr/flatpickr.min.css') }}" rel="stylesheet">

  <!-- Template Main CSS File -->
  <link href="{{ asset('assets/css/main.css') }}" rel="stylesheet">

  <style>
    /* Custom POS Enhancements */
    .pos-quick-nav {
      background: var(--surface-color);
      border: 1px solid var(--border-color);
      border-radius: var(--radius-lg);
      padding: 0.5rem;
    }
    .badge-soft-success { background-color: var(--success-color-light, #caf9d7); color: var(--success-color, #0a863e); }
    .badge-soft-warning { background-color: var(--warning-color-light, #fef9c3); color: var(--warning-color, #ca8a04); }
    .badge-soft-danger { background-color: var(--danger-color-light, #fee2e2); color: var(--danger-color, #dc2626); }
    .badge-soft-info { background-color: var(--info-color-light, #cffafe); color: var(--info-color, #0891b2); }
    .badge-soft-primary { background-color: #e0e7ff; color: #4338ca; }

    /* Bootstrap 5 Pagination Styling & Arrow SVG Constraint */
    .pagination {
      margin-bottom: 0;
      gap: 3px;
    }
    .pagination .page-item .page-link {
      color: var(--text-color, #4b5563);
      border-radius: var(--radius-sm, 6px);
      border: 1px solid var(--border-color, #e5e7eb);
      background-color: var(--surface-color, #ffffff);
      padding: 0.35rem 0.65rem;
      font-size: 0.85rem;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-width: 32px;
      height: 32px;
      line-height: 1;
      transition: all 0.15s ease-in-out;
    }
    .pagination .page-item.active .page-link {
      background-color: var(--primary-color, #0d6efd);
      border-color: var(--primary-color, #0d6efd);
      color: #ffffff;
      font-weight: 600;
    }
    .pagination .page-item.disabled .page-link {
      opacity: 0.45;
      background-color: transparent;
      border-color: var(--border-color, #e5e7eb);
    }
    .pagination .page-item:not(.active):not(.disabled) .page-link:hover {
      background-color: var(--hover-color, #f3f4f6);
      color: var(--primary-color, #0d6efd);
      border-color: var(--primary-color, #0d6efd);
    }
    .pagination svg {
      width: 1rem !important;
      height: 1rem !important;
      max-width: 1rem !important;
      max-height: 1rem !important;
      display: inline-block !important;
    }
  </style>

  @livewireStyles
  @stack('styles')
</head>

<body>
  <!-- Header -->
  <header class="header">
    <!-- Header Left -->
    <div class="header-left">
      <a href="{{ route('dashboard') }}" class="header-logo">
        <img src="{{ \App\Models\SystemSetting::logoUrl() }}" alt="{{ \App\Models\SystemSetting::get('restaurant_name', 'Food Point') }}" style="max-height: 32px; width: auto; object-fit: contain;">
        <span>{{ \App\Models\SystemSetting::get('restaurant_name', 'Food Point') }}</span>
      </a>
      <button class="sidebar-toggle" title="Toggle Sidebar">
        <i class="bi bi-list"></i>
      </button>
    </div>

    <!-- Header Search (Desktop) - Expandable -->
    <div class="header-search">
      <form class="search-form collapsed" action="{{ route('pos.index') }}" method="GET">
        <button type="button" class="search-toggle-btn"><i class="bi bi-search"></i></button>
        <input type="search" name="q" placeholder="Search menu, customer, invoice..." autocomplete="off">
      </form>
    </div>

    <!-- Header Right -->
    <div class="header-right">
      <!-- Quick POS Button -->
      <a href="{{ route('pos.index') }}" class="btn btn-sm btn-primary d-none d-sm-inline-flex align-items-center gap-2 me-2">
        <i class="ph-duotone ph-storefront"></i>
        <span>Live POS</span>
      </a>

      <!-- Desktop Actions -->
      <div class="header-actions-desktop">
        <!-- Theme Toggle -->
        <button class="header-action theme-toggle" title="Toggle Theme">
          <i class="bi bi-moon icon-dark"></i>
          <i class="bi bi-sun icon-light"></i>
        </button>

        <!-- Fullscreen Toggle -->
        <button class="header-action fullscreen-toggle" onclick="toggleFullscreen()" title="Fullscreen">
          <i class="bi bi-fullscreen icon-enter"></i>
          <i class="bi bi-fullscreen-exit icon-exit"></i>
        </button>

        <!-- Cash Drawer Quick Status -->
        <a href="{{ route('cash.shifts') }}" class="header-action" title="Cash Shifts">
          <i class="ph-duotone ph-vault"></i>
        </a>

        <!-- User Dropdown - shadcn style -->
        <div class="header-action dropdown user-dropdown">
          <button class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
            <img src="{{ asset('assets/img/profile-img.webp') }}" alt="User" class="avatar">
          </button>
          <ul class="dropdown-menu dropdown-menu-end">
            <li class="dropdown-header">
              <img src="{{ asset('assets/img/profile-img.webp') }}" alt="User" class="user-avatar">
              <div class="user-info">
                <h6>{{ auth()->user()->name ?? 'Administrator' }}</h6>
                <span>{{ ucfirst(auth()->user()->role ?? 'Super Admin') }}</span>
              </div>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
              <a class="dropdown-item" href="{{ route('profile.index') }}">
                <i class="bi bi-person"></i> Profile
              </a>
            </li>
            <li>
              <a class="dropdown-item" href="{{ route('settings.index') }}">
                <i class="bi bi-gear"></i> System Settings
              </a>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
              <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="dropdown-item dropdown-item-danger w-100 border-0 bg-transparent text-start">
                  <i class="bi bi-box-arrow-right"></i> Sign Out
                </button>
              </form>
            </li>
          </ul>
        </div>
      </div>

      <!-- Mobile Actions -->
      <div class="header-actions-mobile">
        <button class="header-action search-toggle" title="Search"><i class="bi bi-search"></i></button>
        <button class="header-action mobile-menu-toggle" title="More"><i class="bi bi-three-dots-vertical"></i></button>
      </div>
    </div>
  </header>

  <!-- Mobile Search -->
  <div class="mobile-search">
    <form class="search-form" action="{{ route('pos.index') }}" method="GET">
      <input type="search" name="q" placeholder="Search menu, orders..." autocomplete="off">
      <button type="submit"><i class="bi bi-search"></i></button>
    </form>
  </div>

  <!-- Mobile Header Menu -->
  <div class="mobile-header-menu">
    <div class="mobile-header-menu-content">
      <a href="{{ route('pos.index') }}" class="mobile-menu-item">
        <i class="ph-duotone ph-storefront"></i>
        <span class="mobile-menu-label">Open POS</span>
      </a>
      <button class="mobile-menu-item theme-toggle" title="Toggle Theme">
        <i class="bi bi-moon icon-dark"></i>
        <i class="bi bi-sun icon-light"></i>
        <span class="mobile-menu-label">Theme</span>
      </button>
      <a href="{{ route('profile.index') }}" class="mobile-menu-item">
        <i class="bi bi-person"></i>
        <span class="mobile-menu-label">Profile</span>
      </a>
      <form method="POST" action="{{ route('logout') }}" class="w-100">
        @csrf
        <button type="submit" class="mobile-menu-item mobile-menu-item-danger w-100 text-start border-0 bg-transparent">
          <i class="bi bi-box-arrow-right"></i>
          <span class="mobile-menu-label">Sign Out</span>
        </button>
      </form>
    </div>
  </div>

  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="sidebar-header">
      <a href="{{ route('dashboard') }}" class="sidebar-logo">
        <img src="{{ \App\Models\SystemSetting::logoUrl() }}" alt="{{ \App\Models\SystemSetting::get('restaurant_name', 'Food Point') }}" style="max-height: 36px; width: auto; object-fit: contain;">
        <span class="sidebar-logo-text">
          <span class="sidebar-logo-name">{{ \App\Models\SystemSetting::get('restaurant_name', 'Food Point') }}</span>
          <span class="sidebar-logo-tagline">{{ \App\Models\SystemSetting::get('tagline', 'POS & Restaurant ERP') }}</span>
        </span>
      </a>
      <button class="sidebar-close"><i class="bi bi-x-lg"></i></button>
    </div>

    <!-- Sidebar Navigation -->
    <nav class="sidebar-nav">
      <ul class="nav-menu">
        <!-- Dashboard -->
        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}" data-tooltip="Dashboard">
            <i class="ph-duotone ph-squares-four"></i>
            <span>Dashboard</span>
          </a>
        </li>

        <!-- POS / Operations Heading -->
        <li class="nav-heading"><span>POS & Operations</span></li>
        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('pos.index') ? 'active' : '' }}" href="{{ route('pos.index') }}" data-tooltip="Live POS">
            <i class="ph-duotone ph-storefront"></i>
            <span>Live POS</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('orders.*') ? 'active' : '' }}" href="{{ route('orders.index') }}" data-tooltip="Orders & Invoices">
            <i class="ph-duotone ph-receipt"></i>
            <span>Orders / Invoices</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('orders.returns.*') ? 'active' : '' }}" href="{{ route('orders.returns.index') }}" data-tooltip="Sale Returns">
            <i class="ph-duotone ph-arrow-counter-clockwise"></i>
            <span>Sale Returns</span>
          </a>
        </li>
        <li class="nav-item has-submenu {{ request()->routeIs('cash.*') ? 'open' : '' }}">
          <a class="nav-link" href="#" aria-expanded="{{ request()->routeIs('cash.*') ? 'true' : 'false' }}" data-tooltip="Cash & Shifts">
            <i class="ph-duotone ph-vault"></i>
            <span>Cash & Shifts</span>
            <i class="ph-duotone ph-caret-down nav-arrow"></i>
          </a>
          <ul class="nav-submenu">
            <li><a class="nav-link {{ request()->routeIs('cash.shifts*') ? 'active' : '' }}" href="{{ route('cash.shifts') }}">Cashier Shifts</a></li>
            <li><a class="nav-link {{ request()->routeIs('cash.day-close*') ? 'active' : '' }}" href="{{ route('cash.day-close.index') }}"><i class="bi bi-calendar-check me-1 text-primary"></i> Day Close & Z-Report</a></li>
          </ul>
        </li>

        <!-- Restaurant Operations -->
        <li class="nav-heading"><span>Restaurant</span></li>
        <li class="nav-item has-submenu {{ request()->routeIs('restaurant.*') ? 'open' : '' }}">
          <a class="nav-link" href="#" aria-expanded="{{ request()->routeIs('restaurant.*') ? 'true' : 'false' }}" data-tooltip="Restaurant">
            <i class="ph-duotone ph-fork-knife"></i>
            <span>Restaurant Floor</span>
            <i class="ph-duotone ph-caret-down nav-arrow"></i>
          </a>
          <ul class="nav-submenu">
            <li><a class="nav-link {{ request()->routeIs('restaurant.tables') ? 'active' : '' }}" href="{{ route('restaurant.tables') }}">Tables & Sections</a></li>
            <li><a class="nav-link {{ request()->routeIs('restaurant.kitchen') ? 'active' : '' }}" href="{{ route('restaurant.kitchen') }}">Kitchen Display (KOT)</a></li>
            <li><a class="nav-link {{ request()->routeIs('restaurant.delivery') ? 'active' : '' }}" href="{{ route('restaurant.delivery') }}">Delivery Areas</a></li>
            <li><a class="nav-link {{ request()->routeIs('restaurant.riders') ? 'active' : '' }}" href="{{ route('restaurant.riders') }}">Delivery Riders</a></li>
          </ul>
        </li>

        <!-- Catalog / Resources -->
        <li class="nav-heading"><span>Catalog & Resources</span></li>
        <li class="nav-item has-submenu {{ request()->routeIs('resources.*') ? 'open' : '' }}">
          <a class="nav-link" href="#" aria-expanded="{{ request()->routeIs('resources.*') ? 'true' : 'false' }}" data-tooltip="Menu & Catalog">
            <i class="ph-duotone ph-cookie"></i>
            <span>Menu & Catalog</span>
            <i class="ph-duotone ph-caret-down nav-arrow"></i>
          </a>
          <ul class="nav-submenu">
            <li><a class="nav-link {{ request()->routeIs('resources.products.*') ? 'active' : '' }}" href="{{ route('resources.products.index') }}">Products / Menu Items</a></li>
            <li><a class="nav-link {{ request()->routeIs('resources.deals.*') ? 'active' : '' }}" href="{{ route('resources.deals.index') }}"><i class="ph-duotone ph-package me-1 text-primary"></i> Packages & Deals</a></li>
            <li><a class="nav-link {{ request()->routeIs('resources.categories.*') ? 'active' : '' }}" href="{{ route('resources.categories.index') }}">Categories</a></li>
            <li><a class="nav-link {{ request()->routeIs('resources.brands.*') ? 'active' : '' }}" href="{{ route('resources.brands.index') }}">Brands</a></li>
            <li><a class="nav-link {{ request()->routeIs('resources.units.*') ? 'active' : '' }}" href="{{ route('resources.units.index') }}">Units</a></li>
            <li><a class="nav-link {{ request()->routeIs('resources.customers.*') ? 'active' : '' }}" href="{{ route('resources.customers.index') }}">Customers</a></li>
          </ul>
        </li>

        <!-- Inventory -->
        <li class="nav-heading"><span>Inventory</span></li>
        <li class="nav-item has-submenu {{ request()->routeIs('inventory.*') ? 'open' : '' }}">
          <a class="nav-link" href="#" aria-expanded="{{ request()->routeIs('inventory.*') ? 'true' : 'false' }}" data-tooltip="Inventory">
            <i class="ph-duotone ph-archive"></i>
            <span>Inventory</span>
            <i class="ph-duotone ph-caret-down nav-arrow"></i>
          </a>
          <ul class="nav-submenu">
            <li><a class="nav-link {{ request()->routeIs('inventory.overview') ? 'active' : '' }}" href="{{ route('inventory.overview') }}">Stock Overview</a></li>
            <li><a class="nav-link {{ request()->routeIs('inventory.ledger') ? 'active' : '' }}" href="{{ route('inventory.ledger') }}">Stock Ledger</a></li>
            <li><a class="nav-link {{ request()->routeIs('inventory.adjustments') ? 'active' : '' }}" href="{{ route('inventory.adjustments') }}">Stock Adjustment</a></li>
            <li><a class="nav-link {{ request()->routeIs('inventory.purchases') ? 'active' : '' }}" href="{{ route('inventory.purchases') }}">Purchases</a></li>
          </ul>
        </li>

        <!-- Finance & Accounting -->
        <li class="nav-heading"><span>Finance & Accounting</span></li>
        <li class="nav-item has-submenu {{ request()->routeIs('finance.*') ? 'open' : '' }}">
          <a class="nav-link" href="#" aria-expanded="{{ request()->routeIs('finance.*') ? 'true' : 'false' }}" data-tooltip="Finance">
            <i class="ph-duotone ph-coins"></i>
            <span>Finance & Accounts</span>
            <i class="ph-duotone ph-caret-down nav-arrow"></i>
          </a>
          <ul class="nav-submenu">
            <li><a class="nav-link {{ request()->routeIs('finance.chart-of-accounts') ? 'active' : '' }}" href="{{ route('finance.chart-of-accounts') }}">Chart of Accounts</a></li>
            <li><a class="nav-link {{ request()->routeIs('finance.receipts') ? 'active' : '' }}" href="{{ route('finance.receipts') }}">Cash Receipts</a></li>
            <li><a class="nav-link {{ request()->routeIs('finance.payments') ? 'active' : '' }}" href="{{ route('finance.payments') }}">Cash Payments</a></li>
            <li><a class="nav-link {{ request()->routeIs('finance.vouchers') ? 'active' : '' }}" href="{{ route('finance.vouchers') }}">Journal Vouchers</a></li>
            <li><a class="nav-link {{ request()->routeIs('finance.ledger') ? 'active' : '' }}" href="{{ route('finance.ledger') }}">General Ledger</a></li>
            <li><a class="nav-link {{ request()->routeIs('finance.trial-balance') ? 'active' : '' }}" href="{{ route('finance.trial-balance') }}">Trial Balance</a></li>
          </ul>
        </li>

        <!-- Reports -->
        <li class="nav-heading"><span>Reports</span></li>
        <li class="nav-item has-submenu {{ request()->routeIs('reports.*') ? 'open' : '' }}">
          <a class="nav-link" href="#" aria-expanded="{{ request()->routeIs('reports.*') ? 'true' : 'false' }}" data-tooltip="Reports">
            <i class="ph-duotone ph-chart-line-up"></i>
            <span>Reports</span>
            <i class="ph-duotone ph-caret-down nav-arrow"></i>
          </a>
          <ul class="nav-submenu">
            <li><a class="nav-link {{ request()->routeIs('reports.daily-sales') ? 'active' : '' }}" href="{{ route('reports.daily-sales') }}"><i class="bi bi-calendar-check me-1"></i> Day-Wise Sales</a></li>
            <li><a class="nav-link {{ request()->routeIs('reports.shift-sales') ? 'active' : '' }}" href="{{ route('reports.shift-sales') }}"><i class="ph-duotone ph-vault me-1"></i> Shift-Wise Sales</a></li>
            <li><a class="nav-link {{ request()->routeIs('reports.sales') ? 'active' : '' }}" href="{{ route('reports.sales') }}">All Orders / Sales</a></li>
            <li><a class="nav-link {{ request()->routeIs('reports.delivery') ? 'active' : '' }}" href="{{ route('reports.delivery') }}">Delivery & Rider Report</a></li>
            <li><a class="nav-link {{ request()->routeIs('reports.tables') ? 'active' : '' }}" href="{{ route('reports.tables') }}">Table Occupancy Report</a></li>
            <li><a class="nav-link {{ request()->routeIs('reports.products') ? 'active' : '' }}" href="{{ route('reports.products') }}">Product Sales Report</a></li>
            <li><a class="nav-link {{ request()->routeIs('reports.payments') ? 'active' : '' }}" href="{{ route('reports.payments') }}">Payment Method Report</a></li>
            <li><a class="nav-link {{ request()->routeIs('reports.customer-ledger') ? 'active' : '' }}" href="{{ route('reports.customer-ledger') }}">Customer Ledger</a></li>
          </ul>
        </li>

        <!-- Administration -->
        <li class="nav-heading"><span>Administration</span></li>
        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}" data-tooltip="Users">
            <i class="ph-duotone ph-users"></i>
            <span>Users & Roles</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}" href="{{ route('settings.index') }}" data-tooltip="Settings">
            <i class="ph-duotone ph-gear"></i>
            <span>System Settings</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('fbr.*') ? 'active' : '' }}" href="{{ route('fbr.index') }}" data-tooltip="FBR Digital Invoicing">
            <i class="ph-duotone ph-shield-check"></i>
            <span>FBR Digital Invoicing</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ request()->routeIs('audit-logs.*') ? 'active' : '' }}" href="{{ route('audit-logs.index') }}" data-tooltip="Audit Logs">
            <i class="ph-duotone ph-fingerprint"></i>
            <span>Audit Trail</span>
          </a>
        </li>
      </ul>
    </nav>
  </aside>

  <!-- Sidebar Overlay (Mobile) -->
  <div class="sidebar-overlay"></div>

  <!-- Main Content -->
  <main class="main">
    <div class="main-content">
      @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 mb-4" role="alert">
          <i class="bi bi-check-circle-fill"></i>
          <div>{{ session('success') }}</div>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      @endif

      @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 mb-4" role="alert">
          <i class="bi bi-exclamation-octagon-fill"></i>
          <div>{{ session('error') }}</div>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      @endif

      @yield('content')
      {{ $slot ?? '' }}
    </div>

    <!-- Footer -->
    <footer class="footer">
      <div class="footer-content">
        <div class="footer-links">
          <a href="{{ route('pos.index') }}">POS</a>
          <a href="{{ route('orders.index') }}">Orders</a>
          <a href="{{ route('reports.sales') }}">Reports</a>
          <a href="{{ route('settings.index') }}">Settings</a>
        </div>
        <div class="footer-copyright">
          &copy; {{ date('Y') }} <a href="#">Food Point POS</a>. EasyAdmin Pro Edition.
        </div>
      </div>
    </footer>
  </main>

  <!-- Back to Top -->
  <a href="#" class="back-to-top"><i class="bi bi-arrow-up"></i></a>

  <!-- Vendor JS Files -->
  <script src="{{ asset('assets/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
  <script src="{{ asset('assets/vendor/apexcharts/apexcharts.min.js') }}"></script>
  <script src="{{ asset('assets/vendor/simple-datatables/simple-datatables.js') }}"></script>
  <script src="{{ asset('assets/vendor/flatpickr/flatpickr.min.js') }}"></script>
  <script src="{{ asset('assets/vendor/choices.js/choices.min.js') }}"></script>

  <!-- Template Main JS Files -->
  <script src="{{ asset('assets/js/theme.js') }}"></script>
  <script src="{{ asset('assets/js/main.js') }}"></script>

  <script>
    function toggleFullscreen() {
      if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen().catch(err => {
          console.warn('Fullscreen error:', err);
        });
      } else {
        if (document.exitFullscreen) {
          document.exitFullscreen();
        }
      }
    }
  </script>

  @livewireScripts
  @stack('scripts')
</body>
</html>
