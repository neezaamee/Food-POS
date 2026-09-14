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

    /* Print styling: exact 80mm thermal receipt printing matching digital version */
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

      /* Hide everything except the receipt modal and receipt content */
      .pos-navbar,
      .pos-container,
      .pos-wrapper > *:not(.modal),
      .modal-backdrop,
      .modal-header,
      .modal-footer,
      .no-print {
        display: none !important;
      }

      /* Flatten the modal container so it flows naturally on 80mm paper */
      .modal {
        position: static !important;
        display: block !important;
        width: 80mm !important;
        max-width: 80mm !important;
        height: auto !important;
        min-height: auto !important;
        overflow: visible !important;
        background: transparent !important;
        padding: 0 !important;
        margin: 0 !important;
      }

      .modal-dialog {
        position: static !important;
        display: block !important;
        width: 80mm !important;
        max-width: 80mm !important;
        margin: 0 !important;
        padding: 0 !important;
        transform: none !important;
      }

      .modal-content {
        position: static !important;
        border: none !important;
        box-shadow: none !important;
        background: #fff !important;
        padding: 0 !important;
        margin: 0 !important;
        width: 80mm !important;
        max-width: 80mm !important;
      }

      #printableReceipt {
        display: block !important;
        position: static !important;
        width: 80mm !important;
        max-width: 80mm !important;
        padding: 4mm 4mm !important;
        margin: 0 !important;
        background: #fff !important;
        font-family: 'Courier New', Courier, monospace !important;
        font-size: 13px !important;
        line-height: 1.3 !important;
        color: #000 !important;
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
        font-size: 12px !important;
      }

      #printableReceipt .border-top {
        border-top: 1px dashed #000 !important;
      }

      #printableReceipt .border-bottom {
        border-bottom: 1px dashed #000 !important;
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
