<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    // GET /api/superadmin/users
    public function index()
    {
        $users = User::with(['roles', 'tenant'])->get();

        return response()->json([
            'users' => $users,
            'total_users' => $users->count(),
        ]);
    }

    // GET /api/superadmin/users/{id}
    public function show($id)
    {
        $user = User::with(['roles', 'tenant'])->findOrFail($id);

        return response()->json(['user' => $user]);
    }

    // POST /api/superadmin/users
    public function store(Request $request)
{
    $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|string|email|max:255|unique:users',
        'password' => 'required|string|confirmed|min:6',
        'role' => 'required|string|exists:roles,name',
        'tenant_id' => 'nullable|exists:tenants,id',
    ]);

    $user = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => bcrypt($request->password),
        'tenant_id' => $request->tenant_id, // picked from dropdown
    ]);

    $user->assignRole($request->role);

    return response()->json([
        'message' => 'User created successfully (Super Admin)',
        'user' => $user,
        'roles' => $user->getRoleNames(),
    ]);
}


    // PUT /api/superadmin/users/{id}
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name'  => 'nullable|string|max:255',
            'email' => ['nullable','email', Rule::unique('users','email')->ignore($user->id)],
            'password' => 'nullable|string|min:6|confirmed',
            'roles' => 'nullable|array',
            'roles.*' => 'string',
            'tenant_id' => 'nullable|exists:tenants,id',
        ]);

        if ($request->filled('name')) $user->name = $request->name;
        if ($request->filled('email')) $user->email = $request->email;
        if ($request->filled('tenant_id')) $user->tenant_id = $request->tenant_id;
        if ($request->filled('password')) $user->password = Hash::make($request->password);

        $user->save();

        if ($request->filled('roles')) {
            $user->syncRoles($request->roles);
        }

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user->load('roles', 'tenant'),
        ]);
    }

    // DELETE /api/superadmin/users/{id}
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        // Prevent deleting the very first super-admin if you want
        if ($user->id === 1) {
            return response()->json(['error' => 'Cannot delete primary Super Admin'], 403);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }
   // UserController.php
public function getRoles(Request $request)
{
    $user = $request->user();

    if ($user->hasRole('Super Admin')) {
        return response()->json( Role::pluck('name'));
    }

    if ($user->hasRole('Tenant Admin')) {
        return response()->json(['Manager', 'Agent']);
    }

    return response()->json([]);
}

}
