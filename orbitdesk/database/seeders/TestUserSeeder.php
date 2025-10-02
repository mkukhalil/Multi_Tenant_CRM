<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class TestUserSeeder extends Seeder
{
    public function run()
    {
        // Disable FK checks for reset
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        User::truncate();
        Tenant::truncate();
        DB::statement('ALTER TABLE users AUTO_INCREMENT = 1');
        DB::statement('ALTER TABLE tenants AUTO_INCREMENT = 1');
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Create a tenant
        $tenant = Tenant::create([
            'name' => 'Acme Corp',
            'slug' => 'acme-corp',
        ]);

        // Super Admin (system-wide, no tenant restriction)
        $superAdmin = User::create([
            'name' => 'Khalil',
            'email' => 'khalil@gmail.com',
            'password' => Hash::make('password'),
            'tenant_id' => null, // super admin is outside tenant scope
        ]);
        $superAdmin->assignRole('Super Admin');

        // Tenant Admin
        $admin = User::create([
            'name' => 'Farooq',
            'email' => 'farooq@gmail.com',
            'password' => Hash::make('password'),
            'tenant_id' => $tenant->id,
        ]);
        $admin->assignRole('Tenant Admin');

        // Manager
        $manager = User::create([
            'name' => 'Manager User',
            'email' => 'manager@acme.com',
            'password' => Hash::make('password123'),
            'tenant_id' => $tenant->id,
        ]);
        $manager->assignRole('Manager');

        // Agent
        $agent = User::create([
            'name' => 'Agent User',
            'email' => 'agent@acme.com',
            'password' => Hash::make('password123'),
            'tenant_id' => $tenant->id,
        ]);
        $agent->assignRole('Agent');
    }
}
