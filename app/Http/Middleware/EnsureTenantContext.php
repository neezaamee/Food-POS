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

            // 1. Super-admin impersonation check
            if ($user->role === 'super-admin' || empty($user->tenant_id)) {
                if (session()->has('impersonated_tenant_id')) {
                    $impersonated = Tenant::find(session('impersonated_tenant_id'));
                    if ($impersonated) {
                        $context->setTenant($impersonated);
                    }
                } else {
                    // Default super admin to their own tenant or Tenant 1 for seamless POS operations
                    $context->setTenant($user->tenant ?: Tenant::find(1));
                }
            } else {
                // 2. Standard tenant user
                $tenant = $user->tenant;

                if (! $tenant) {
                    Auth::logout();

                    return redirect()->route('login')->withErrors(['email' => 'Your account is not associated with an active food point/tenant.']);
                }

                if ($tenant->isSuspended()) {
                    return response()->view('saas.suspended', ['tenant' => $tenant], 403);
                }

                $context->setTenant($tenant);
            }
        }

        return $next($request);
    }
}
