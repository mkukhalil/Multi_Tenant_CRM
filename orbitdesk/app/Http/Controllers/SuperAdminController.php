<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tenant;
use App\Models\User;
use Spatie\Permission\Models\Role;

/**
 * Controller for Super Admin functions.
 *
 * @mixin \Illuminate\Routing\Controller
 */
class SuperAdminController extends Controller
{
    
    /**
     * SuperAdmin dashboard stats
     */
    public function dashboard()
    {
        return response()->json([
            'status'        => 'success',
            'message'       => 'Super Admin dashboard data',
            'tenants_count' => Tenant::count(),
            'users_count'   => User::count(),
        ]);
    }

    /**
     * List all tenants with user count
     */
    public function tenants()
    {
        $tenants = Tenant::withCount('users')->get();

        return response()->json([
            'status'  => 'success',
            'message' => 'All tenants fetched successfully',
            'data'    => $tenants,
        ]);
    }

    /**
     * Get details of a specific tenant (including its users)
     */
   public function tenantDetails($id)
{
    $tenant = Tenant::with(['users.roles'])->find($id);

    if (!$tenant) {
        return response()->json([
            'status' => 'error',
            'message' => 'Tenant not found'
        ], 404);
    }

    return response()->json([
        'status' => 'success',
        'message' => 'Tenant details fetched successfully',
        'data' => $tenant
    ], 200);
}
public function roles()
    {
        $roles = Role::all(['id', 'name']);
        return response()->json([
            'status' => 'success',
            'roles'  => $roles
        ]);
    }
      public function allTenants()
    {
        $tenants = Tenant::select('id', 'name')->get();
        return response()->json([
            'status'  => 'success',
            'tenants' => $tenants
        ]);
    }
public function leadsCount()
{
    $totalLeads = \App\Models\Lead::count();
    return response()->json(['total_leads' => $totalLeads]);
}

}
