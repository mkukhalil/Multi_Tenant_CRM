<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Define permissions
        $permissions = [
            'view leads',
            'create leads',
            'edit leads',
            'delete leads',
            'view users',
            'create users',
            'edit users',
            'delete users',
            // Add more as needed: tasks, clients, reports...
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Define roles
        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin']); // Full system access
        $tenantAdmin = Role::firstOrCreate(['name' => 'Tenant Admin']); // Admin inside one tenant
        $manager = Role::firstOrCreate(['name' => 'Manager']); // Mid-level role
        $agent = Role::firstOrCreate(['name' => 'Agent']); // Basic role

        // Assign permissions
        $superAdmin->givePermissionTo(Permission::all());

        $tenantAdmin->givePermissionTo([
            'view leads',
            'create leads',
            'edit leads',
            'delete leads',
            'view users',
            'create users',
            'edit users',
        ]);

        $manager->givePermissionTo([
            'view leads',
            'create leads',
            'edit leads',
        ]);

        $agent->givePermissionTo([
            'view leads',
        ]);
    }
}
