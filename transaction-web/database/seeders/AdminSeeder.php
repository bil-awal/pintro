<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Admin::firstOrCreate(
            ['email' => 'admin@pintro.com'],
            [
                'name' => 'Super Admin',
                'email' => 'admin@pintro.com',
                'password' => Hash::make('password123'),
                'roles' => ['super-admin', 'admin'],
                'permissions' => ['*'],
                'is_active' => true,
            ]
        );

        Admin::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'password' => Hash::make('password'),
                'roles' => ['admin'],
                'permissions' => ['manage-users', 'manage-transactions'],
                'is_active' => true,
            ]
        );
    }
}
