<?php

namespace App\Traits;

use App\Models\Tenant;
use App\Scopes\TenantScope;
use App\Services\SaaS\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTenant
{
    /**
     * Boot the trait to attach global TenantScope and auto-assign tenant_id
     */
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model) {
            if (empty($model->tenant_id)) {
                $context = app(TenantContext::class);
                if ($context->hasTenant()) {
                    $model->tenant_id = $context->getTenantId();
                } elseif (auth()->check() && auth()->user()->getActiveTenantId()) {
                    $model->tenant_id = auth()->user()->getActiveTenantId();
                } elseif (! empty($model->order_id) && method_exists($model, 'order') && $model->order) {
                    $model->tenant_id = $model->order->tenant_id;
                } elseif (! empty($model->journal_entry_id) && method_exists($model, 'entry') && $model->entry) {
                    $model->tenant_id = $model->entry->tenant_id;
                } elseif (! empty($model->purchase_id) && method_exists($model, 'purchase') && $model->purchase) {
                    $model->tenant_id = $model->purchase->tenant_id;
                } elseif (! empty($model->cash_shift_id) && method_exists($model, 'shift') && $model->shift) {
                    $model->tenant_id = $model->shift->tenant_id;
                } else {
                    $model->tenant_id = 1;
                }
            }
        });
    }

    /**
     * Relationship to the Tenant
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Scope to bypass tenant filtering when explicitly required
     */
    public function scopeWithoutTenant(Builder $query): Builder
    {
        return $query->withoutGlobalScope(TenantScope::class);
    }
}
