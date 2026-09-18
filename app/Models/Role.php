<?php

namespace App\Models;

use App\Services\SaaS\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'description',
        'is_system',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
        ];
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permission');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'role_user');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function hasPermission(string $permissionSlug): bool
    {
        return $this->permissions->contains('slug', $permissionSlug);
    }

    public function isSystem(): bool
    {
        return (bool) ($this->is_system || $this->tenant_id === null || in_array($this->slug, [
            'super-admin', 'super_admin', 'owner', 'admin', 'manager',
            'cashier', 'waiter', 'kitchen-staff', 'delivery-manager',
            'rider', 'accountant',
        ]));
    }

    public function isDeletable(): bool
    {
        return ! $this->isSystem() && $this->users()->count() === 0;
    }

    /**
     * Scope query to roles accessible by the given tenant (tenant custom roles + system global roles)
     */
    public function scopeForTenant(Builder $query, ?int $tenantId = null): Builder
    {
        $tenantId = $tenantId ?? app(TenantContext::class)->getTenantId() ?? auth()->user()?->getActiveTenantId();

        return $query->where(function ($q) use ($tenantId) {
            $q->whereNull('tenant_id');
            if ($tenantId) {
                $q->orWhere('tenant_id', $tenantId);
            }
        });
    }

    public function scopeSystem(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('is_system', true)->orWhereNull('tenant_id');
        });
    }

    public function scopeCustom(Builder $query): Builder
    {
        return $query->where('is_system', false)->whereNotNull('tenant_id');
    }
}
