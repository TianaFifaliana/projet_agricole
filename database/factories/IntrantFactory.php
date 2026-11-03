<?php

namespace Database\Factories;

use App\Models\Exploitation;
use App\Models\Intrant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Intrant>
 */
class IntrantFactory extends Factory
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
            'nom' => ucfirst(fake()->unique()->word()),
            'categorie' => fake()->randomElement(['engrais', 'semence', 'phytosanitaire', 'aliment', 'autre']),
            'unite' => fake()->randomElement(['kg', 'L', 'sacs', 'tonnes']),
            'seuil_alerte' => fake()->randomFloat(2, 10, 200),
            'prix_unitaire' => fake()->randomFloat(2, 1, 500),
            'actif' => true,
        ];
    }
}
