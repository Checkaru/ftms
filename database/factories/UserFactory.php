<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => UserRole::Student,
            'phone' => fake()->numerify('05########'),
            'student_number' => null,
            'organization_id' => null,
            'is_active' => true,
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function student(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Student,
            'student_number' => (string) fake()->unique()->numberBetween(2020_1000, 2026_9999),
        ]);
    }

    /** A field supervisor is attached to a specific host organisation. */
    public function fieldSupervisor(Organization|int|null $organization = null): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::FieldSupervisor,
            'organization_id' => $organization instanceof Organization
                ? $organization->id
                : $organization,
        ]);
    }

    public function academicSupervisor(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::AcademicSupervisor,
        ]);
    }

    public function coordinator(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Coordinator,
        ]);
    }
}
