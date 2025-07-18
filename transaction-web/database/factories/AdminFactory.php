<?php

namespace Database\Factories;

use App\Models\Admin;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Admin>
 */
class AdminFactory extends Factory
{
    protected $model = Admin::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'roles' => $this->faker->randomElements(['admin', 'manager', 'operator'], $this->faker->numberBetween(1, 2)),
            'permissions' => $this->generatePermissions(),
            'is_active' => $this->faker->boolean(90), // 90% chance of being active
            'last_login_at' => $this->faker->optional(0.8)->dateTimeThisMonth(),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the admin is a super admin.
     */
    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'roles' => ['super_admin'],
            'permissions' => ['*'],
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the admin is a transaction manager.
     */
    public function transactionManager(): static
    {
        return $this->state(fn (array $attributes) => [
            'roles' => ['transaction_manager'],
            'permissions' => [
                'view_transactions',
                'approve_transactions',
                'reject_transactions',
                'view_users',
                'manage_users',
                'view_reports',
            ],
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the admin is an operator.
     */
    public function operator(): static
    {
        return $this->state(fn (array $attributes) => [
            'roles' => ['operator'],
            'permissions' => [
                'view_transactions',
                'view_users',
                'view_reports',
            ],
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the admin is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'last_login_at' => null,
        ]);
    }

    /**
     * Generate permissions based on common roles.
     */
    private function generatePermissions(): array
    {
        $allPermissions = [
            'view_transactions',
            'create_transactions',
            'edit_transactions',
            'delete_transactions',
            'approve_transactions',
            'reject_transactions',
            'view_users',
            'create_users',
            'edit_users',
            'delete_users',
            'manage_users',
            'view_reports',
            'export_reports',
            'view_settings',
            'manage_settings',
            'view_logs',
            'manage_admins',
        ];

        // Return a random subset of permissions
        return $this->faker->randomElements(
            $allPermissions, 
            $this->faker->numberBetween(3, count($allPermissions))
        );
    }
}
