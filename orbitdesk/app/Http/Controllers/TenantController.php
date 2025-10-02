<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Illuminate\Routing\Controller as BaseController;

class TenantController extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    // ...

    public function registerTenant(Request $request)
{
    $request->validate([
        'tenant_name'     => 'required|string|unique:tenants,name',
        'admin_name'      => 'required|string|max:255',
        'email'           => 'required|email|unique:users',
        'password'        => 'required|string|min:8|confirmed',
    ]);

    // Create tenant
    $tenant = Tenant::create([
        'name' => $request->tenant_name,
        'slug' => Str::slug($request->tenant_name),
    ]);

    // Create first user (Tenant Admin)
    $user = User::create([
        'tenant_id' => $tenant->id,
        'name'      => $request->admin_name,
        'email'     => $request->email,
        'password'  => Hash::make($request->password),
    ]);

    // Assign Tenant Admin role
    $tenantAdminRole = Role::firstOrCreate(['name' => 'Tenant Admin']);
    $user->assignRole($tenantAdminRole);

    $token = $user->createToken('API Token')->plainTextToken;

    return response()->json([
        'status'  => 'success',
        'message' => 'Tenant and Admin registered successfully',
        'tenant'  => $tenant,
        'user'    => $user,
        'token'   => $token,
    ]);
}


    /**
     * Dashboard data for logged-in user’s tenant.
     */
   public function dashboard(Request $request)
    {
        $tenant = $request->user()->tenant;

        if (!$tenant) {
            return response()->json(['error' => 'No tenant found'], 404);
        }

        $users = $tenant->users()->with('roles')->get();

        // sample counts - adjust field names if your migrations differ
        $leadsCount = $tenant->leads()->count();
        $activeTasks = method_exists($tenant, 'tasks') ? $tenant->tasks()->where('status', 'active')->count() : 0;

        return response()->json([
            'tenant' => $tenant,
            'users' => $users,
            'leads_count' => $leadsCount,
            'active_tasks' => $activeTasks,
        ]);
    }


    /**
     * List all users of logged-in user’s tenant.
     */
    public function users(Request $request)
    {
        $tenant = $request->user()->tenant;

        return $tenant
            ? $tenant->users()->with('roles')->get()
            : response()->json([], 200);
    }
}
