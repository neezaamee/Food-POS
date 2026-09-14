<?php

namespace App\Models;

use App\Services\SaaS\TenantContext;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'phone',
        'role',
        'status',
        'avatar',
        'password',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }

    public function ownedTenants()
    {
        return $this->belongsToMany(Tenant::class, 'tenant_owners')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super-admin' || $this->role === 'super_admin';
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner' || $this->ownedTenants()->exists();
    }

    public function getActiveTenantId(): ?int
    {
        return $this->tenant_id ?? $this->ownedTenants()->first()?->id;
    }

    public function hasRole(string $roleSlug): bool
    {
        // Only actual super-admins can satisfy super-admin role checks
        if ($roleSlug === 'super-admin' || $roleSlug === 'super_admin') {
            return $this->isSuperAdmin();
        }

        if ($this->isSuperAdmin()) {
            return true;
        }

        // Store Owners have manager and administrative powers within their store
        if ($this->isOwner()) {
            if (in_array($roleSlug, ['owner', 'manager', 'admin'])) {
                return true;
            }
        }

        return $this->role === $roleSlug || $this->roles->contains('slug', $roleSlug);
    }

    public function hasPermission(string $permissionSlug): bool
    {
        if ($this->isSuperAdmin() || $this->isOwner()) {
            return true;
        }

        foreach ($this->roles as $role) {
            if ($role->hasPermission($permissionSlug)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Scope query to a specific tenant
     */
    public function scopeForTenant(Builder $query, ?int $tenantId = null): Builder
    {
        $tenantId = $tenantId ?? app(TenantContext::class)->getTenantId() ?? auth()->user()?->getActiveTenantId();

        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope query to store-level staff (exclude platform super-admins)
     */
    public function scopeStoreStaff(Builder $query): Builder
    {
        return $query->whereNotIn('role', ['super-admin', 'super_admin']);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function canCancelOrder(): bool
    {
        $allowed = ['super-admin', 'super_admin', 'owner', 'manager', 'admin'];
        $userRole = strtolower(str_replace('_', '-', (string) ($this->role ?? '')));

        if (in_array($userRole, ['super-admin', 'owner', 'manager', 'admin'])) {
            return true;
        }

        return $this->roles()->whereIn('slug', $allowed)->exists();
    }
}
