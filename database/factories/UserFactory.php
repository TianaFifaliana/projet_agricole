<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\Exploitation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
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
            'exploitation_id' => Exploitation::factory(),
            'nom' => fake()->lastName(),
            'prenom' => fake()->firstName(),
            'email' => fake()->unique()->safeEmail(),
            'telephone' => fake()->phoneNumber(),
            'role' => UserRole::Agent,
            'actif' => true,
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function administrateur(): static
    {
        return $this->state(fn (array $attributes): array => [
            'exploitation_id' => null,
            'role' => UserRole::Administrateur,
        ]);
    }

    public function responsable(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role' => UserRole::Responsable,
        ]);
    }

    public function agent(): static
    {
        return $this->state(fn (array $attributes): array => [
            'role' => UserRole::Agent,
        ]);
    }

    public function pourExploitation(Exploitation $exploitation): static
    {
        return $this->state(fn (array $attributes): array => [
            'exploitation_id' => $exploitation->getKey(),
        ]);
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
}
