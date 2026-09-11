<?php

namespace App\Http\Middleware;

use App\Services\SaaS\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FeatureGate
{
    /**
     * Protect routes based on subscription plan feature flags.
     */
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $tenant = app(TenantContext::class)->getTenant();

        // If no tenant context or super-admin, allow
        if (! $tenant || $tenant->id === 1) {
            return $next($request);
        }

        if (! $tenant->canAccessFeature($feature)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'ok' => false,
                    'error' => "This feature ('{$feature}') is not included in your current subscription plan. Please upgrade to access this module.",
                ], 403);
            }

            return redirect()->route('dashboard')->with('error', "Module '{$feature}' is not included in your subscription plan. Please contact administrator to upgrade.");
        }

        return $next($request);
    }
}
