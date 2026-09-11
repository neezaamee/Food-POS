<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Suspended - {{ $tenant->name ?? 'Food Point POS' }}</title>
    <link href="{{ asset('assets/vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/vendor/bootstrap-icons/bootstrap-icons.css') }}" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center min-vh-100 p-3">
    <div class="card border-0 shadow-sm rounded-4 text-center p-4 p-md-5" style="max-width: 520px;">
        <div class="mb-3 text-danger">
            <i class="bi bi-shield-lock-fill" style="font-size: 3.5rem;"></i>
        </div>
        <h4 class="fw-bold mb-2">Food Point Account Suspended</h4>
        <p class="text-muted small mb-4">
            The subscription for <strong>{{ $tenant->name }}</strong> has been suspended due to overdue billing or platform policy. Access to the POS terminal and business data is temporarily restricted.
        </p>
        <div class="p-3 bg-light rounded-3 text-start small border mb-4">
            <div class="d-flex justify-content-between py-1 border-bottom">
                <span class="text-muted">Business:</span>
                <strong>{{ $tenant->name }}</strong>
            </div>
            <div class="d-flex justify-content-between py-1 border-bottom">
                <span class="text-muted">Status:</span>
                <span class="badge bg-danger">Suspended</span>
            </div>
            <div class="d-flex justify-content-between py-1">
                <span class="text-muted">Support Contact:</span>
                <strong>support@foodpointpos.com</strong>
            </div>
        </div>
        <div class="d-flex justify-content-center gap-2">
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-outline-secondary btn-sm px-4">Log Out</button>
            </form>
        </div>
    </div>
</body>
</html>
