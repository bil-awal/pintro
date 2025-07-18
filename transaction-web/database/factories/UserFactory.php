<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->optional(0.8)->phoneNumber(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'balance' => fake()->randomFloat(2, 0, 1000000),
            'status' => fake()->randomElement(['active', 'inactive', 'suspended']),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the user is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Indicate that the user is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }

    /**
     * Indicate that the user is suspended.
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'suspended',
            'balance' => 0.00,
        ]);
    }

    /**
     * Indicate that the user has a high balance.
     */
    public function wealthy(): static
    {
        return $this->state(fn (array $attributes) => [
            'balance' => fake()->randomFloat(2, 1000000, 10000000),
            'status' => 'active',
        ]);
    }

    /**
     * Indicate that the user has no balance.
     */
    public function broke(): static
    {
        return $this->state(fn (array $attributes) => [
            'balance' => 0.00,
        ]);
    }

    /**
     * Indicate that the user has a phone number.
     */
    public function withPhone(): static
    {
        return $this->state(fn (array $attributes) => [
            'phone' => '+62' . fake()->numerify('##########'),
        ]);
    }
}
