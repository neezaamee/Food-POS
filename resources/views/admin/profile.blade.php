@extends('layouts.app')

@section('title', 'My Profile & Account')

@section('content')
<div class="row g-4 mb-4">
    <div class="col-12">
        <h4 class="mb-1 fw-bold text-body-emphasis">My Profile</h4>
        <p class="text-muted mb-0 small">Manage your account credentials and personal information</p>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
    <ul class="mb-0 small">
        @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
        @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-3 text-center p-4">
            <div class="mx-auto rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center fw-bold fs-2 mb-3" style="width: 80px; height: 80px;">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </div>
            <h5 class="fw-bold mb-1">{{ $user->name }}</h5>
            <div class="text-muted small mb-2">{{ $user->email }}</div>
            <div>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1">
                    {{ ucfirst(str_replace(['-', '_'], ' ', $user->role)) }}
                </span>
            </div>
            <hr class="my-4">
            <div class="text-start small text-muted">
                <div class="mb-2"><strong>Status:</strong> <span class="badge bg-success-subtle text-success ms-1">{{ ucfirst($user->status) }}</span></div>
                <div class="mb-2"><strong>Phone:</strong> {{ $user->phone ?? 'Not provided' }}</div>
                <div><strong>Joined:</strong> {{ $user->created_at->format('M d, Y') }}</div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-transparent border-0 pt-4 px-4 pb-2">
                <h6 class="fw-bold mb-0">Update Account Details</h6>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('admin.profile.update') }}">
                    @csrf
                    @method('PUT')
                    
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label small fw-medium">Display Name</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-medium">Email Address (Read-only)</label>
                            <input type="email" class="form-control bg-light" value="{{ $user->email }}" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-medium">Phone Number</label>
                            <input type="text" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}">
                        </div>
                    </div>

                    <h6 class="fw-bold border-top pt-4 mb-3">Security & Password</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-12">
                            <label class="form-label small fw-medium">Current Password (Required only if changing password)</label>
                            <input type="password" name="current_password" class="form-control" placeholder="Enter current password">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-medium">New Password</label>
                            <input type="password" name="new_password" class="form-control" placeholder="Minimum 6 characters">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-medium">Confirm New Password</label>
                            <input type="password" name="new_password_confirmation" class="form-control" placeholder="Repeat new password">
                        </div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary px-4">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
