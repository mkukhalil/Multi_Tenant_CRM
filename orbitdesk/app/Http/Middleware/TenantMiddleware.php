<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class TenantMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        // 1️⃣ Check authentication
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // 2️⃣ Allow Super Admin (ID=1) unrestricted access
        if ($user->hasRole('Super Admin')) {
            return $next($request);
        }

        // 3️⃣ Ensure user belongs to a tenant
        if (!$user->tenant) {
            return response()->json(['error' => 'No tenant assigned'], 403);
        }

        // 4️⃣ Tenant exists → proceed
        return $next($request);
    }
}
