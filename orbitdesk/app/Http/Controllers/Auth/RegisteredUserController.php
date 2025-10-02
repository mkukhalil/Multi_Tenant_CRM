<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Show the registration page.
     */
    public function create(): Response
    {
        return Inertia::render('auth/register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
{
    $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|string|email|max:255|unique:users',
        'password' => 'required|string|confirmed|min:8',
    ]);

    // Case 1: First ever user in system -> Super Admin
    if (User::count() === 0) {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);
        $user->assignRole('Super Admin');
        return response()->json(['message' => 'Super Admin created', 'user' => $user]);
    }

    // Case 2: New tenant
    if ($request->has('tenant_name')) {
        $tenant = \App\Models\Tenant::create([
            'name' => $request->tenant_name,
            'slug' => Str::slug($request->tenant_name)
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'tenant_id' => $tenant->id,
        ]);

        $user->assignRole('Admin');
        return response()->json(['message' => 'New Tenant Admin created', 'user' => $user]);
    }

    // Case 3: Join existing tenant
    if ($request->has('tenant_id')) {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'tenant_id' => $request->tenant_id,
        ]);

        $user->assignRole('User');
        return response()->json(['message' => 'User added to tenant', 'user' => $user]);
    }

    return response()->json(['error' => 'Invalid registration flow'], 400);
}

public function createNewTenant(): Response
{
    return Inertia::render('auth/register-new-tenant');
}

public function createExistingTenant(): Response
{
    return Inertia::render('auth/register-existing-tenant', [
        'tenants' => \App\Models\Tenant::all(['id', 'name'])
    ]);
}

}
