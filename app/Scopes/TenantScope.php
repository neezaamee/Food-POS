<?php

namespace App\Scopes;

use App\Services\SaaS\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class TenantScope implements Scope
{
    /**
     * Apply the tenant filter to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (TenantContext::hasTenant()) {
            $builder->where($model->getTable().'.tenant_id', TenantContext::getTenantId());
        } elseif (Auth::check() && Auth::user()->getActiveTenantId() && ! TenantContext::instance()->isBypassingTenant()) {
            $builder->where($model->getTable().'.tenant_id', Auth::user()->getActiveTenantId());
        }
    }
}
