<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ $title ?? 'Sign In - Food Point POS' }}</title>

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
  <link href="{{ asset('assets/vendor/phosphor-icons/phosphor-icons.css') }}" rel="stylesheet">

  <!-- Template Main CSS File -->
  <link href="{{ asset('assets/css/main.css') }}" rel="stylesheet">
</head>

<body>
  <div class="auth-layout">
    <div class="auth-container">
      <!-- Logo -->
      <a href="{{ url('/') }}" class="auth-logo">
        <img src="{{ \App\Models\SystemSetting::logoUrl() }}" alt="{{ \App\Models\SystemSetting::get('restaurant_name', 'Food Point') }}" style="max-height: 48px; width: auto; object-fit: contain;">
        <span>{{ \App\Models\SystemSetting::get('restaurant_name', 'Food Point POS') }}</span>
      </a>

      @yield('content')

      <!-- Footer -->
      <footer class="footer-centered">
        <div class="footer-copyright">
          &copy; {{ date('Y') }} <a href="#">Food Point POS</a>. All Rights Reserved.
        </div>
      </footer>
    </div>
  </div>

  <!-- Vendor JS Files -->
  <script src="{{ asset('assets/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
  <script src="{{ asset('assets/js/theme.js') }}"></script>
  <script src="{{ asset('assets/js/main.js') }}"></script>
</body>
</html>
