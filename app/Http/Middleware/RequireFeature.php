<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Services\SaaS\FeatureAccessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireFeature
{
    public function __construct(
        protected FeatureAccessService $featureAccessService
    ) {}

    /**
     * Handle an incoming request and check if tenant has feature entitlement.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $user = $request->user();

        // Super-admin without explicit tenant context has unrestricted platform access
        if ($user && $user->isSuperAdmin() && ! session()->has('current_tenant_id')) {
            return $next($request);
        }

        $tenantId = session('current_tenant_id') ?? $user?->getActiveTenantId();

        if (! $tenantId) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'No active tenant context found.',
                ], Response::HTTP_FORBIDDEN);
            }

            return redirect()->route('dashboard')->with('error', 'No active business context found.');
        }

        $tenant = Tenant::find($tenantId);

        if (! $tenant) {
            abort(Response::HTTP_NOT_FOUND, 'Business not found.');
        }

        if ($tenant->isDisabled() || $tenant->isSuspended()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'This business account is currently '.$tenant->status.'.',
                ], Response::HTTP_FORBIDDEN);
            }

            return redirect()->route('dashboard')->with('error', 'This business account is currently '.$tenant->status.'.');
        }

        if (! $this->featureAccessService->allows($tenant, $feature)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Feature not included in current subscription plan.',
                    'feature' => $feature,
                    'upgrade_required' => true,
                ], Response::HTTP_FORBIDDEN);
            }

            return redirect()->route('dashboard')->with(
                'error',
                "Your subscription plan does not include access to the '{$feature}' module. Please upgrade your plan or contact support."
            );
        }

        return $next($request);
    }
}
