<?php

namespace Database\Factories;

use App\Models\Animal;
use App\Models\Exploitation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Animal>
 */
class AnimalFactory extends Factory
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
            'identifiant' => 'AN-'.fake()->unique()->numerify('#####'),
            'nom' => fake()->firstName(),
            'espece' => fake()->randomElement(['bovin', 'ovin', 'caprin', 'porcin', 'volaille']),
            'race' => fake()->optional()->word(),
            'sexe' => fake()->randomElement(['M', 'F']),
            'date_naissance' => fake()->dateTimeBetween('-8 years', '-2 months'),
            'poids_kg' => fake()->randomFloat(2, 5, 900),
            'statut' => 'actif',
            'date_entree' => fake()->dateTimeBetween('-5 years', 'now'),
            'date_sortie' => null,
        ];
    }
}
