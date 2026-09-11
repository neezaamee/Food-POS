@extends('layouts.auth', ['title' => 'Sign In - Food Point POS'])

@section('content')
<div class="auth-card">
  <div class="auth-card-header">
    <h1 class="auth-title">Welcome back</h1>
    <p class="auth-subtitle">Sign in to access Food Point POS system</p>
  </div>

  @if ($errors->any())
    <div class="alert alert-danger py-2 px-3 small mb-3">
      {{ $errors->first() }}
    </div>
  @endif

  <form class="auth-form" method="POST" action="{{ route('login.post') }}">
    @csrf
    <div class="form-group mb-3">
      <label for="email" class="form-label">Email Address</label>
      <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', 'admin@foodpoint.com') }}" required autofocus>
    </div>

    <div class="form-group mb-3">
      <div class="d-flex justify-content-between align-items-center mb-1">
        <label for="password" class="form-label mb-0">Password</label>
      </div>
      <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" value="password" required>
    </div>

    <div class="form-check mb-3">
      <input class="form-check-input" type="checkbox" id="remember" name="remember" checked>
      <label class="form-check-label" for="remember">Remember me</label>
    </div>

    <button type="submit" class="btn btn-primary w-100 py-2">Sign In</button>

    <div class="text-center mt-3 small">
      <span class="text-muted">New restaurant owner?</span>
      <a href="{{ route('tenant.register') }}" class="fw-semibold text-primary text-decoration-none">Register Food Point</a>
    </div>
  </form>

  <div class="mt-4 pt-3 border-top text-center text-muted small">
    <div><strong>Demo Credentials:</strong></div>
    <div>Admin: <code>admin@foodpoint.com</code> | <code>password</code></div>
    <div>Cashier: <code>cashier@foodpoint.com</code> | <code>password</code></div>
  </div>
</div>
@endsection
