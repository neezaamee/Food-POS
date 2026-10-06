<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ $title ?? (\App\Models\SystemSetting::get('restaurant_name') ? \App\Models\SystemSetting::get('restaurant_name') . ' - POS' : 'Food Point POS') }}</title>

  <!-- PWA Manifest & Theme Color for Offline Support -->
  <link rel="manifest" href="{{ asset('manifest.json') }}">
  <meta name="theme-color" content="#0d6efd">

  <!-- Favicons -->
  <link href="{{ asset('assets/img/favicon.png') }}" rel="icon">

  <!-- Google Fonts - Inter -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="{{ asset('assets/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
  <link href="{{ asset('assets/vendor/bootstrap-icons/bootstrap-icons.css') }}" rel="stylesheet">
  <link href="{{ asset('assets/vendor/phosphor-icons/phosphor-icons.css') }}" rel="stylesheet">

  <!-- Template Main CSS File -->
  <link href="{{ asset('assets/css/main.css') }}" rel="stylesheet">

  <style>
    body.pos-body {
      background-color: var(--background-color, #fafafa);
      color: var(--default-color, #3f3f46);
      overflow-x: hidden;
      margin: 0;
      padding: 0;
      font-family: var(--default-font);
    }

    .pos-navbar {
      min-height: 56px;
      height: 56px;
      background: var(--surface-color, #ffffff);
      border-bottom: 1px solid var(--border-color, #e4e4e7);
      padding: 0 0.75rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      position: sticky;
      top: 0;
      z-index: 1000;
      gap: 0.5rem;
    }

    @media (min-width: 1400px) {
      .pos-navbar {
        padding: 0 1.25rem;
        gap: 0.75rem;
      }
    }

    .pos-container {
      height: calc(100vh - 56px);
      display: flex;
      overflow: hidden;
    }

    .badge-soft-success { background-color: var(--success-color-light, #caf9d7); color: var(--success-color, #0a863e); }
    .badge-soft-warning { background-color: var(--warning-color-light, #fef9c3); color: var(--warning-color, #ca8a04); }
    .badge-soft-danger { background-color: var(--danger-color-light, #fee2e2); color: var(--danger-color, #dc2626); }
    .badge-soft-info { background-color: var(--info-color-light, #cffafe); color: var(--info-color, #0891b2); }

    /* Custom slim scrollbar for cart */
    #posCartScrollContainer::-webkit-scrollbar {
      width: 5px;
    }
    #posCartScrollContainer::-webkit-scrollbar-track {
      background: transparent;
    }
    #posCartScrollContainer::-webkit-scrollbar-thumb {
      background: #cbd5e1;
      border-radius: 4px;
    }
    #posCartScrollContainer::-webkit-scrollbar-thumb:hover {
      background: #94a3b8;
    }

    /* 14-inch / Compact Screen Optimizations (viewport height <= 820px or width <= 1400px) */
    @media (max-height: 820px) {
      .pos-navbar {
        min-height: 46px !important;
        height: 46px !important;
        padding: 0 0.5rem !important;
      }
      .pos-container {
        height: calc(100vh - 46px) !important;
      }
      .pos-cart-header {
        padding-top: 0.35rem !important;
        padding-bottom: 0.35rem !important;
      }
      .pos-customer-strip {
        padding-top: 0.25rem !important;
        padding-bottom: 0.25rem !important;
      }
      .pos-summary-box {
        padding: 0.4rem 0.6rem !important;
      }
      .pos-cart-item {
        padding-top: 0.2rem !important;
        padding-bottom: 0.2rem !important;
      }
      .pos-action-btn {
        padding-top: 0.3rem !important;
        padding-bottom: 0.3rem !important;
      }
    }

    /* Mobile Screens & Handheld POS (<= 767.98px) */
    @media (max-width: 767.98px) {
      .pos-navbar {
        min-height: 48px !important;
        height: 48px !important;
        padding: 0 0.4rem !important;
        gap: 0.25rem !important;
      }
      .pos-container {
        height: calc(100vh - 48px) !important;
        height: calc(100dvh - 48px) !important;
        flex-direction: column !important;
        position: relative !important;
        overflow: hidden !important;
      }
      .pos-catalog-panel,
      .pos-cart-panel {
        width: 100% !important;
        max-width: 100% !important;
        flex: 1 1 100% !important;
        height: 100% !important;
        border-end: none !important;
      }
      .pos-mobile-cart-bar {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        z-index: 1030;
        padding: 0.6rem 0.75rem;
        padding-bottom: calc(0.6rem + env(safe-area-inset-bottom, 0px));
        background: var(--surface-color, #ffffff);
        border-top: 1px solid var(--border-color, #e4e4e7);
        box-shadow: 0 -4px 16px rgba(0, 0, 0, 0.12);
      }
      .pos-products-scroll-mobile {
        padding-bottom: 75px !important;
      }
      .pos-cart-stepper {
        width: 92px !important;
        height: 32px !important;
      }
      .pos-cart-stepper button {
        width: 30px !important;
        font-size: 0.95rem !important;
        font-weight: bold !important;
      }
      .pos-cart-stepper input {
        font-size: 0.9rem !important;
      }
      .pos-action-btn {
        padding-top: 0.55rem !important;
        padding-bottom: 0.55rem !important;
        font-size: 0.85rem !important;
      }
      .pos-customer-strip input,
      .pos-customer-strip select {
        font-size: 0.9rem !important;
      }
    }

    /* Print styling: exact 80mm thermal receipt printing matching KOT format */
    @page {
      margin: 0;
      size: 80mm auto;
    }
    @media print {
      *,
      *::before,
      *::after {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        color-adjust: exact !important;
        box-sizing: border-box !important;
      }

      html,
      body {
        width: 80mm !important;
        max-width: 80mm !important;
        background: #fff !important;
        color: #000 !important;
        margin: 0 !important;
        padding: 0 !important;
        height: auto !important;
        min-height: auto !important;
        overflow: visible !important;
      }

      /* Exact 80mm thermal receipt printing */
      body * {
        visibility: hidden !important;
      }

      #printableReceipt,
      #printableReceipt * {
        visibility: visible !important;
      }

      #printableReceipt {
        position: absolute !important;
        left: 0 !important;
        top: 0 !important;
        width: 80mm !important;
        max-width: 80mm !important;
        margin: 0 !important;
        padding: 3mm 3mm !important;
        background: #fff !important;
        color: #000 !important;
        display: block !important;
        overflow: visible !important;
        font-family: 'Courier New', Courier, monospace, 'Noto Nastaliq Urdu', 'Jameel Noori Nastaleeq' !important;
        font-size: 14px !important;
        font-weight: 600 !important;
        line-height: 1.35 !important;
      }

      .no-print,
      .pos-navbar,
      .pos-container,
      .modal-backdrop,
      .modal-header,
      .modal-footer {
        display: none !important;
      }

      #printableReceipt img {
        display: block !important;
        margin: 0 auto 4px auto !important;
        max-height: 48px !important;
        max-width: 140px !important;
        object-fit: contain !important;
        image-rendering: -webkit-optimize-contrast !important;
        image-rendering: crisp-edges !important;
      }

      #printableReceipt table {
        width: 100% !important;
        border-collapse: collapse !important;
      }

      #printableReceipt table th,
      #printableReceipt table td {
        font-size: 14px !important;
      }

      #printableReceipt .border-top {
        border-top: 1.5px dashed #000 !important;
      }

      #printableReceipt .border-bottom {
        border-bottom: 1.5px dashed #000 !important;
      }
    }
  </style>

  @livewireStyles
  @stack('styles')
</head>

<body class="pos-body">
  @if(\App\Services\SaaS\TenantContext::instance()->isImpersonating())
    <div class="bg-warning text-dark px-3 py-2 d-flex flex-wrap align-items-center justify-content-between shadow-sm no-print" style="position: sticky; top: 0; z-index: 99999; font-size: 0.85rem;">
      <div class="d-flex align-items-center gap-2">
        <i class="ph-duotone ph-warning-octagon fs-5"></i>
        <span><strong>IMPERSONATING:</strong> Managing POS for <strong>{{ \App\Services\SaaS\TenantContext::current()?->name }}</strong>.</span>
      </div>
      <a href="{{ route('saas.exit-impersonation') }}" class="btn btn-sm btn-dark text-white fw-semibold d-inline-flex align-items-center gap-1 shadow-sm">
        <i class="ph-duotone ph-sign-out"></i>
        <span>Exit Impersonation</span>
      </a>
    </div>
  @endif

  <div class="pos-wrapper">
    @yield('content')
    {{ $slot ?? '' }}
  </div>

  <!-- Vendor JS Files -->
  <script src="{{ asset('assets/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
  <script src="{{ asset('assets/js/theme.js') }}"></script>

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

    // Global Thermal Print Dispatcher
    window.printReceipt = function(url) {
      if (url) {
        var printWin = window.open(url, '_blank', 'width=420,height=700,menubar=no,toolbar=no,location=no,status=no');
        if (printWin) {
          window.activePrintPopup = printWin;
          printWin.focus();
          return;
        }
      }
      window.print();
    };
    window.printReceiptModal = window.printReceipt;

    // Global Esc key listener on master POS layout
    window.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') {
        if (window.activePrintPopup && !window.activePrintPopup.closed) {
          try {
            window.activePrintPopup.close();
            window.activePrintPopup = null;
          } catch (err) {}
        }
      }
    });
  </script>

  @livewireScripts
  
  <!-- POS Offline Engine & IndexedDB Sync Scripts -->
  <script>
    window.posEndpoints = {
      ping: "{{ route('pos.api.ping') }}",
      catalog: "{{ route('pos.api.catalog') }}",
      sync: "{{ route('pos.api.sync') }}"
    };
  </script>
  <script src="{{ asset('assets/js/pos-offline-db.js') }}"></script>
  <script src="{{ asset('assets/js/pos-offline-printer.js') }}"></script>
  <script src="{{ asset('assets/js/pos-offline-engine.js') }}"></script>
  <script>
    if ('serviceWorker' in navigator) {
      navigator.serviceWorker.getRegistrations().then(function(registrations) {
        for (let registration of registrations) {
          registration.unregister();
        }
      });
      if ('caches' in window) {
        caches.keys().then(function(names) {
          for (let name of names) {
            caches.delete(name);
          }
        });
      }
    }
  </script>

  @stack('scripts')
</body>
</html>
