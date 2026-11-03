<?php

namespace Database\Factories;

use App\Models\Exploitation;
use App\Models\Parcelle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Parcelle>
 */
class ParcelleFactory extends Factory
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
            'code' => 'P-'.fake()->unique()->numerify('###'),
            'nom' => 'Parcelle '.fake()->word(),
            'superficie_ha' => fake()->randomFloat(2, 0.5, 40),
            'type_sol' => fake()->randomElement(['argileux', 'limoneux', 'sableux', 'calcaire']),
            'latitude' => fake()->latitude(43, 51),
            'longitude' => fake()->longitude(-4, 8),
            'actif' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'actif' => false,
        ]);
    }
}
