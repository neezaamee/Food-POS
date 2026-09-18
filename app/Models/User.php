<?php

namespace App\Models;

use App\Services\SaaS\TenantContext;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

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

    protected ?Collection $cachedPermissions = null;

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
        return in_array($this->role, ['owner', 'admin']) || $this->ownedTenants()->exists();
    }

    public function getActiveTenantId(): ?int
    {
        return $this->tenant_id ?? $this->ownedTenants()->first()?->id;
    }

    /**
     * Check if user has a given role or any of the given roles
     *
     * @param  string|array<int, string>  $roleSlug
     */
    public function hasRole(string|array $roleSlug): bool
    {
        $roleSlugs = is_array($roleSlug) ? $roleSlug : [$roleSlug];

        // Only actual super-admins satisfy super-admin role checks
        if (in_array('super-admin', $roleSlugs) || in_array('super_admin', $roleSlugs)) {
            return $this->isSuperAdmin();
        }

        if ($this->isSuperAdmin()) {
            return true;
        }

        // Store Owners have manager and administrative powers within their store
        if ($this->isOwner() && ! empty(array_intersect(['owner', 'manager', 'admin'], $roleSlugs))) {
            return true;
        }

        $userRole = str_replace('_', '-', (string) $this->role);
        $roleSlugsNormalized = array_map(fn ($s) => str_replace('_', '-', $s), $roleSlugs);

        if (in_array($userRole, $roleSlugsNormalized)) {
            return true;
        }

        return $this->roles->contains(fn ($r) => in_array(str_replace('_', '-', $r->slug), $roleSlugsNormalized));
    }

    /**
     * Get all permission slugs granted to this user via roles
     *
     * @return Collection<int, string>
     */
    public function getAllPermissions(): Collection
    {
        if ($this->cachedPermissions !== null) {
            return $this->cachedPermissions;
        }

        // Eager load permissions on roles if not already loaded to prevent N+1
        if (! $this->relationLoaded('roles')) {
            $this->load('roles.permissions');
        } else {
            $this->roles->loadMissing('permissions');
        }

        $this->cachedPermissions = $this->roles
            ->flatMap(fn ($role) => $role->permissions)
            ->pluck('slug')
            ->unique()
            ->values();

        return $this->cachedPermissions;
    }

    /**
     * Clear cached permissions in memory
     */
    public function clearPermissionCache(): void
    {
        $this->cachedPermissions = null;
    }

    public function hasPermission(string $permissionSlug): bool
    {
        if ($this->isSuperAdmin() || $this->isOwner()) {
            return true;
        }

        return $this->getAllPermissions()->contains($permissionSlug);
    }

    /**
     * Check if user has any of the given permissions
     *
     * @param  array<int, string>  $permissionSlugs
     */
    public function hasAnyPermission(array $permissionSlugs): bool
    {
        if ($this->isSuperAdmin() || $this->isOwner()) {
            return true;
        }

        $userPermissions = $this->getAllPermissions();

        foreach ($permissionSlugs as $slug) {
            if ($userPermissions->contains($slug)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user has all of the given permissions
     *
     * @param  array<int, string>  $permissionSlugs
     */
    public function hasAllPermissions(array $permissionSlugs): bool
    {
        if ($this->isSuperAdmin() || $this->isOwner()) {
            return true;
        }

        $userPermissions = $this->getAllPermissions();

        foreach ($permissionSlugs as $slug) {
            if (! $userPermissions->contains($slug)) {
                return false;
            }
        }

        return true;
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
        if ($this->hasPermission('pos.cancel-order')) {
            return true;
        }

        $allowed = ['super-admin', 'super_admin', 'owner', 'manager', 'admin'];
        $userRole = strtolower(str_replace('_', '-', (string) ($this->role ?? '')));

        if (in_array($userRole, ['super-admin', 'owner', 'manager', 'admin'])) {
            return true;
        }

        return $this->roles()->whereIn('slug', $allowed)->exists();
    }
}
