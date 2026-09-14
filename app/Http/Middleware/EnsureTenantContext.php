<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Services\SaaS\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantContext
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();
            $context = app(TenantContext::class);

            // 1. Super-admin context handling
            if ($user->isSuperAdmin()) {
                if (session()->has('impersonated_tenant_id')) {
                    $impersonated = Tenant::find(session('impersonated_tenant_id'));
                    if ($impersonated) {
                        $context->setTenant($impersonated);
                    }
                } elseif ($request->is('saas-admin*')) {
                    // SaaS platform admin views cross-tenant data without tenant filter
                    $context->setTenant(null);
                } else {
                    // Default super admin in store POS view to their own tenant or Tenant 1
                    $context->setTenant($user->tenant ?: Tenant::find(1));
                }
            } else {
                // 2. Business Owner or Store Staff
                $tenant = $user->tenant ?? $user->ownedTenants()->first();

                if (! $tenant) {
                    Auth::logout();

                    return redirect()->route('login')->withErrors(['email' => 'Your account is not associated with an active business.']);
                }

                if ($tenant->isSuspended() || $tenant->isDisabled()) {
                    return response()->view('saas.suspended', ['tenant' => $tenant], 403);
                }

                $context->setTenant($tenant);
            }
        }

        return $next($request);
    }
}
