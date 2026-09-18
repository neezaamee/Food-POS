<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return redirect()->route('login');
        }

        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Support piped syntax e.g. "manager|admin"
        $required = [];
        foreach ($roles as $role) {
            foreach (explode('|', $role) as $sub) {
                $trimmed = trim($sub);
                if ($trimmed !== '') {
                    $required[] = $trimmed;
                }
            }
        }

        if (empty($required)) {
            return $next($request);
        }

        if ($user->hasRole($required)) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => 'Unauthorized. Required role: '.implode(', ', $required),
            ], 403);
        }

        abort(403, 'Unauthorized. You do not possess the required role to access this page.');
    }
}
