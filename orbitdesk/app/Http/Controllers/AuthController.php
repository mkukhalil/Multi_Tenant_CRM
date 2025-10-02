<?php

namespace App\Http\Controllers;

use App\Models\User;
use Spatie\Permission\Models\Role;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        // Validate all fields
        $request->validate([
            'tenant_name' => 'required|string|unique:tenants,name',
            'admin_name'  => 'required|string|max:255',
            'email'       => 'required|string|email|max:255|unique:users',
            'password'    => 'required|string|min:6|confirmed',
        ]);

        // 1. Create Tenant
        $tenant = Tenant::create([
            'name' => $request->tenant_name,
            'slug' => Str::slug($request->tenant_name),
        ]);

        // 2. Create first Admin user
        $user = User::create([
            'name'      => $request->admin_name,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
            'tenant_id' => $tenant->id,
        ]);

        // 3. Assign Tenant Admin Role
        $role = Role::firstOrCreate(['name' => 'Tenant Admin']);
        $user->assignRole($role);

        // 4. Create API token
        $token = $user->createToken('API Token')->plainTextToken;

        return response()->json([
            'status'  => 'success',
            'message' => 'Tenant and Admin registered successfully',
            'tenant'  => $tenant,
            'user'    => $user,
            'token'   => $token,
        ], 201);
    }



   public function login(Request $request)
{
    if (!Auth::attempt($request->only('email', 'password'))) {
        return response()->json(['message' => 'Invalid login details'], 401);
    }

    $user = Auth::user();
    $token = $user->createToken('auth_token')->plainTextToken;

    return response()->json([
        'access_token' => $token,
        'token_type' => 'Bearer',
        'roles' => $user->getRoleNames(), // <--- Add this
        'user' => $user, // optional: send user info
    ]);
}


    public function user(Request $request)
    {
        return $request->user();
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }
     public function registerCompany(Request $request)
    {
        $request->validate([
            'company_name' => 'required|string|max:255|unique:tenants,name',
            'name'         => 'required|string|max:255',
            'email'        => 'required|string|email|max:255|unique:users,email',
            'password'     => 'required|string|min:6|confirmed',
        ]);

        // 1. Create Tenant
        $tenant = Tenant::create([
            'name' => $request->company_name,
            'slug' => Str::slug($request->company_name),
        ]);

        // 2. Create First User (Tenant Admin)
        $user = User::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
            'tenant_id' => $tenant->id,
        ]);

        // TODO: Assign role = 'Tenant Admin' (we will add roles later)

        // 3. Create API Token
        $token = $user->createToken('API Token')->plainTextToken;

        return response()->json([
            'message'      => 'Company and first admin registered successfully',
            'tenant'       => $tenant,
            'user'         => $user,
            'access_token' => $token,
            'token_type'   => 'Bearer',
        ], 201);
    }
}
