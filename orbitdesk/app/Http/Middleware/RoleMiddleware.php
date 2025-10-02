<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, $role)
    {
        $user = $request->user();

        // Super Admin override: allow access to everything
        if ($user && $user->hasRole('Super Admin')) {
            return $next($request);
        }

        // Regular role check
        if (!$user || !$user->hasRole($role)) {
            abort(403, 'Unauthorized'); // 403 Forbidden
        }

        return $next($request);
    }
}
