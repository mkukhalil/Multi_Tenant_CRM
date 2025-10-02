<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Accept either explicit role or legacy ID=1
        if (!($user->isSuperAdmin() || $user->hasRole('Super Admin'))) {
            return response()->json(['message' => 'Forbidden — SuperAdmin only.'], 403);
        }

        return $next($request);
    }
}
