<?php

namespace Database\Factories;

use App\Models\Culture;
use App\Models\Exploitation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Culture>
 */
class CultureFactory extends Factory
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
            'nom' => fake()->randomElement(['Blé tendre', 'Maïs', 'Orge', 'Colza', 'Tournesol', 'Luzerne', 'Prairie']),
            'variete' => fake()->optional()->word(),
            'duree_cycle_jours' => fake()->numberBetween(60, 240),
            'rendement_cible_tha' => fake()->randomFloat(2, 2, 12),
            'actif' => true,
        ];
    }
}
