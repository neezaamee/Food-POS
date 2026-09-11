<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Food Point / Restaurant - Cloud POS</title>

    <link href="{{ asset('assets/img/favicon.png') }}" rel="icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <link href="{{ asset('assets/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/vendor/bootstrap-icons/bootstrap-icons.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/vendor/phosphor-icons/phosphor-icons.css') }}" rel="stylesheet">

    <style>
        :root {
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --surface: #ffffff;
            --background: #f8fafc;
            --border: #e2e8f0;
            --text-dark: #0f172a;
            --text-muted: #64748b;
        }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            min-height: 100vh;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }
        .register-card {
            background: var(--surface);
            border-radius: 1.5rem;
            box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.15);
            border: 1px solid var(--border);
            max-width: 800px;
            width: 100%;
            overflow: hidden;
        }
        .form-control, .form-select {
            border-radius: 0.75rem;
            padding: 0.65rem 1rem;
            border: 1px solid #cbd5e1;
            font-size: 0.95rem;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
        }
        .plan-radio:checked + label {
            border-color: var(--primary) !important;
            background-color: #eff6ff !important;
        }
        .btn-register {
            background-color: var(--primary);
            border: none;
            border-radius: 0.75rem;
            padding: 0.85rem;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.2s ease;
        }
        .btn-register:hover {
            background-color: var(--primary-hover);
            transform: translateY(-1px);
        }
    </style>
</head>
<body>
    <div class="register-card p-4 p-md-5">
        <div class="text-center mb-4">
            <div class="d-inline-flex p-3 rounded-circle bg-primary bg-opacity-10 text-primary mb-3">
                <i class="ph-duotone ph-storefront fs-1"></i>
            </div>
            <h2 class="fw-bold text-dark mb-1">Start Your Restaurant POS</h2>
            <p class="text-muted">Launch your cloud-based food point with a 14-day free trial. No credit card required.</p>
        </div>

        @if($errors->any())
            <div class="alert alert-danger rounded-4 border-0 shadow-sm mb-4">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <strong>Please correct the following errors:</strong>
                </div>
                <ul class="mb-0 small ps-3">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('tenant.register.submit') }}" method="POST">
            @csrf

            <!-- Section 1: Food Point Details -->
            <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">
                <i class="ph-duotone ph-fork-knife me-1 text-primary"></i>1. Restaurant Information
            </h5>
            <div class="row g-3 mb-4">
                <div class="col-md-7">
                    <label class="form-label fw-semibold">Restaurant / Food Point Name <span class="text-danger">*</span></label>
                    <input type="text" name="restaurant_name" id="restaurant_name" class="form-control" placeholder="e.g. Bella Pizza, Burger Shack" value="{{ old('restaurant_name') }}" required autofocus>
                </div>
                <div class="col-md-5">
                    <label class="form-label fw-semibold">Subdomain / Slug <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="text" name="slug" id="restaurant_slug" class="form-control" placeholder="e.g. bella-pizza" value="{{ old('slug') }}" required>
                    </div>
                    <span class="text-muted small">Unique ID for your food point</span>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Contact Phone</label>
                    <input type="text" name="phone" class="form-control" placeholder="e.g. +92 300 1234567" value="{{ old('phone') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Primary Currency</label>
                    <select name="currency" class="form-select">
                        <option value="PKR" {{ old('currency') === 'PKR' ? 'selected' : '' }}>PKR (Rs - Pakistani Rupee)</option>
                        <option value="USD" {{ old('currency') === 'USD' ? 'selected' : '' }}>USD ($ - US Dollar)</option>
                        <option value="AED" {{ old('currency') === 'AED' ? 'selected' : '' }}>AED (United Arab Emirates Dirham)</option>
                        <option value="SAR" {{ old('currency') === 'SAR' ? 'selected' : '' }}>SAR (Saudi Riyal)</option>
                        <option value="GBP" {{ old('currency') === 'GBP' ? 'selected' : '' }}>GBP (£ - British Pound)</option>
                    </select>
                </div>
            </div>

            <!-- Section 2: Owner / Admin Account -->
            <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">
                <i class="ph-duotone ph-user me-1 text-primary"></i>2. Owner Admin Account
            </h5>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Your Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" placeholder="e.g. John Doe" value="{{ old('name') }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Email Address (Login Username) <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control" placeholder="owner@restaurant.com" value="{{ old('email') }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Password <span class="text-danger">*</span></label>
                    <input type="password" name="password" class="form-control" placeholder="Minimum 6 characters" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Confirm Password <span class="text-danger">*</span></label>
                    <input type="password" name="password_confirmation" class="form-control" placeholder="Confirm your password" required>
                </div>
            </div>

            <!-- Section 3: Select Plan -->
            <h5 class="fw-bold text-dark border-bottom pb-2 mb-3">
                <i class="ph-duotone ph-credit-card me-1 text-primary"></i>3. Select Your Plan (14-Day Free Trial)
            </h5>
            <div class="row g-3 mb-4">
                @foreach($plans as $plan)
                <div class="col-md-4">
                    <input type="radio" class="btn-check plan-radio" name="plan_id" id="plan_{{ $plan->id }}" value="{{ $plan->id }}" {{ (old('plan_id', $plans->first()?->id) == $plan->id) ? 'checked' : '' }} required>
                    <label class="card h-100 p-3 border rounded-4 cursor-pointer text-start w-100" for="plan_{{ $plan->id }}" style="cursor: pointer;">
                        <span class="badge bg-primary bg-opacity-10 text-primary w-auto align-self-start mb-2 px-2 py-1">{{ $plan->name }}</span>
                        <div class="h4 fw-bold text-dark mb-1">${{ number_format($plan->price, 0) }} <span class="fs-6 text-muted fw-normal">/ {{ $plan->billing_cycle }}</span></div>
                        <span class="small text-muted mb-2 d-block">{{ $plan->description ?? 'All core POS features' }}</span>
                        <span class="badge bg-success bg-opacity-10 text-success small fw-semibold mt-auto">14 Days Free</span>
                    </label>
                </div>
                @endforeach
            </div>

            <button type="submit" class="btn btn-primary btn-register w-100 text-white shadow-sm mb-3">
                <i class="ph-duotone ph-rocket-launch me-2"></i>Create Food Point & Launch POS
            </button>

            <div class="text-center small text-muted">
                Already have an account? <a href="{{ route('login') }}" class="fw-semibold text-primary text-decoration-none">Sign In</a>
            </div>
        </form>
    </div>

    <script>
        // Automatic slug generation from Restaurant Name
        const nameInput = document.getElementById('restaurant_name');
        const slugInput = document.getElementById('restaurant_slug');

        nameInput.addEventListener('input', function() {
            if (!slugInput.dataset.manual) {
                slugInput.value = this.value
                    .toLowerCase()
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/^-+|-+$/g, '');
            }
        });

        slugInput.addEventListener('input', function() {
            slugInput.dataset.manual = "true";
        });
    </script>
</body>
</html>
