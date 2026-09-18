<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequirePermission
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if (! $user) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return redirect()->route('login');
        }

        if ($user->isSuperAdmin() || $user->isOwner()) {
            return $next($request);
        }

        // Support piped syntax e.g. "reports.view|reports.sales" or array of arguments
        $required = [];
        foreach ($permissions as $perm) {
            foreach (explode('|', $perm) as $sub) {
                $trimmed = trim($sub);
                if ($trimmed !== '') {
                    $required[] = $trimmed;
                }
            }
        }

        if (empty($required)) {
            return $next($request);
        }

        foreach ($required as $permission) {
            if ($user->hasPermission($permission)) {
                return $next($request);
            }
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => 'Unauthorized. You do not have permission to access this resource.',
                'required' => $required,
            ], 403);
        }

        abort(403, 'Unauthorized. You do not have the required permissions to access this page.');
    }
}
