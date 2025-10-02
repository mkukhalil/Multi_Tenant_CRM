<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureTenant
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Must belong to a tenant
        if (!$user->tenant_id) {
            return response()->json(['message' => 'Forbidden — Tenant account required.'], 403);
        }

        // If the route includes a tenant identifier, ensure it matches the user's tenant
        $tenantId = $request->route('tenant') ?? $request->route('tenant_id') ?? $request->route('id');

        if ($tenantId && (int)$tenantId !== (int)$user->tenant_id) {
            return response()->json(['message' => 'Forbidden — tenant mismatch.'], 403);
        }

        return $next($request);
    }
}
