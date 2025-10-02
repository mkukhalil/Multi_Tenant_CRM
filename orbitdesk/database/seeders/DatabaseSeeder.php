<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Run in order
        $this->call([
            RolePermissionSeeder::class,
            TestUserSeeder::class,
        ]);
    }
}
