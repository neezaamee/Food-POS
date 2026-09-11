<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdmin
{
    /**
     * Handle an incoming request for SaaS platform super-admin only.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        if ($user->role === 'super-admin' || $user->hasRole('super-admin') || empty($user->tenant_id)) {
            return $next($request);
        }

        abort(403, 'Unauthorized. Access restricted to SaaS Platform Super Administrators.');
    }
}
