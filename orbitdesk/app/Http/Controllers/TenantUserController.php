<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
class TenantUserController extends Controller
{
    // GET /api/tenant/users
    public function index(Request $request)
    {
        $tenant = $request->user()->tenant;

        if (!$tenant) {
            return response()->json(['error' => 'No tenant found'], 404);
        }

        $users = $tenant->users()->with('roles')->get();

        return response()->json([
            'users' => $users,
            'total_users' => $users->count(),
            'total_leads' => $tenant->leads()->count(),
        ]);
    }

    // GET /api/tenant/users/{id}
    public function show(Request $request, $id)
    {
        $tenant = $request->user()->tenant;
        $user = $tenant->users()->with('roles')->findOrFail($id);

        return response()->json(['user' => $user]);
    }

    // POST /api/tenant/users
    public function store(Request $request)
{
    $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|string|email|max:255|unique:users',
        'password' => 'required|string|confirmed|min:6',
        'role' => 'required|string|exists:roles,name',
    ]);

    $tenantId = Auth::user()->tenant_id; // force tenant

    $user = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => bcrypt($request->password),
        'tenant_id' => $tenantId,
    ]);

    $user->assignRole($request->role);

    return response()->json([
        'message' => 'User created successfully (Tenant Admin)',
        'user' => $user,
        'roles' => $user->getRoleNames(),
    ]);
}


    // PUT /api/tenant/users/{id}
    public function update(Request $request, $id)
    {
        $tenant = $request->user()->tenant;
        $user = $tenant->users()->findOrFail($id);

        $request->validate([
            'name' => 'nullable|string|max:255',
            'email' => ['nullable','email', Rule::unique('users','email')->ignore($user->id)],
            'password' => 'nullable|string|min:6|confirmed',
            'roles' => 'nullable|array',
            'roles.*' => 'string',
        ]);

        if ($request->filled('name')) $user->name = $request->name;
        if ($request->filled('email')) $user->email = $request->email;
        if ($request->filled('password')) $user->password = Hash::make($request->password);

        $user->save();

        if ($request->filled('roles')) {
            // roles could be provided as ['Agent'] or 'Agent' in older code; normalize
            $user->syncRoles($request->roles);
        }

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user->load('roles'),
        ]);
    }

    // DELETE /api/tenant/users/{id}
    public function destroy(Request $request, $id)
    {
        $tenant = $request->user()->tenant;
        $user = $tenant->users()->findOrFail($id);

        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }

    // GET /api/tenant/roles
    // GET /api/tenant/roles
public function roles()
{
    $roles = Role::where('name', '!=', 'Super Admin')
                 ->pluck('name'); // just names

    return response()->json($roles);
}



}
