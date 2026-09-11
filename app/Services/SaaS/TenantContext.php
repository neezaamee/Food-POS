<?php

namespace App\Services\SaaS;

use App\Models\Tenant;

class TenantContext
{
    protected ?Tenant $tenant = null;

    protected ?Tenant $impersonatedTenant = null;

    protected bool $bypassTenantScope = false;

    /**
     * Resolve singleton instance from Laravel service container
     */
    public static function instance(): self
    {
        return app(self::class);
    }

    /**
     * Get active tenant
     */
    public function activeTenant(): ?Tenant
    {
        if ($this->impersonatedTenant) {
            return $this->impersonatedTenant;
        }

        return $this->tenant;
    }

    /**
     * Get active tenant ID
     */
    public function tenantId(): ?int
    {
        return $this->activeTenant()?->id;
    }

    /**
     * Check if tenant is active
     */
    public function isTenantActive(): bool
    {
        return ! $this->bypassTenantScope && $this->tenantId() !== null;
    }

    /**
     * Set active tenant
     */
    public function setTenant(?Tenant $tenant): void
    {
        $this->tenant = $tenant;
    }

    /**
     * Start impersonation
     */
    public function startImpersonation(Tenant $tenant): void
    {
        $this->impersonatedTenant = $tenant;
        if (function_exists('session') && session()->isStarted()) {
            session()->put('impersonated_tenant_id', $tenant->id);
        }
    }

    /**
     * Exit impersonation
     */
    public function exitImpersonation(): void
    {
        $this->impersonatedTenant = null;
        if (function_exists('session') && session()->isStarted()) {
            session()->forget('impersonated_tenant_id');
        }
    }

    /**
     * Check if currently impersonating
     */
    public function isImpersonating(): bool
    {
        if ($this->impersonatedTenant !== null) {
            return true;
        }

        return function_exists('session') && session()->isStarted() && session()->has('impersonated_tenant_id');
    }

    public function isBypassingTenant(): bool
    {
        return $this->bypassTenantScope;
    }

    /**
     * Run callback bypassing tenant isolation scope
     */
    public function withoutTenant(callable $callback)
    {
        $previous = $this->bypassTenantScope;
        $this->bypassTenantScope = true;

        try {
            return $callback();
        } finally {
            $this->bypassTenantScope = $previous;
        }
    }

    // Static facade methods
    public static function getTenant(): ?Tenant
    {
        return static::instance()->activeTenant();
    }

    public static function current(): ?Tenant
    {
        return static::instance()->activeTenant();
    }

    public static function getTenantId(): ?int
    {
        return static::instance()->tenantId();
    }

    public static function id(): ?int
    {
        return static::instance()->tenantId();
    }

    public static function hasTenant(): bool
    {
        return static::instance()->isTenantActive();
    }

    public static function check(): bool
    {
        return static::instance()->isTenantActive();
    }

    public static function set(?Tenant $tenant): void
    {
        static::instance()->setTenant($tenant);
    }
}
