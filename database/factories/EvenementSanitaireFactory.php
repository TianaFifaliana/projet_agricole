<?php

namespace Database\Factories;

use App\Models\Animal;
use App\Models\EvenementSanitaire;
use App\Models\Exploitation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EvenementSanitaire>
 */
class EvenementSanitaireFactory extends Factory
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
            'animal_id' => Animal::factory(),
            'type' => fake()->randomElement(['vaccination', 'traitement', 'visite', 'vermifuge']),
            'date_evenement' => fake()->dateTimeBetween('-1 year', 'now'),
            'produit' => fake()->optional()->word(),
            'dose' => fake()->optional()->numerify('## ml'),
            'veterinaire' => fake()->optional()->name(),
            'date_fin_carence' => fake()->optional()->dateTimeBetween('now', '+1 month'),
            'cout' => fake()->optional()->randomFloat(2, 10, 1000),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (EvenementSanitaire $evenement): void {
            $animal = Animal::withoutGlobalScopes()->find($evenement->animal_id);

            if ($animal !== null) {
                $evenement->exploitation_id = $animal->exploitation_id;
            }
        });
    }
}
