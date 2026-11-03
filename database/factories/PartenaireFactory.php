<?php

namespace Database\Factories;

use App\Models\Exploitation;
use App\Models\Partenaire;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Partenaire>
 */
class PartenaireFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'exploitation_id' => Exploitation::factory(),
            'type' => fake()->randomElement(['client', 'fournisseur', 'les_deux']),
            'nom' => fake()->company(),
            'email' => fake()->unique()->companyEmail(),
            'telephone' => fake()->phoneNumber(),
            'adresse' => fake()->address(),
            'actif' => true,
        ];
    }

    public function client(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => 'client',
        ]);
    }
}
