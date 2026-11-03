<?php

namespace Database\Factories;

use App\Enums\TypeIntervention;
use App\Models\Exploitation;
use App\Models\Intervention;
use App\Models\Parcelle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Intervention>
 */
class InterventionFactory extends Factory
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
            'parcelle_id' => Parcelle::factory(),
            'cycle_culture_id' => null,
            'intrant_id' => null,
            'operateur_id' => null,
            'type' => fake()->randomElement([
                TypeIntervention::Preparation,
                TypeIntervention::Semis,
                TypeIntervention::Fertilisation,
                TypeIntervention::Traitement,
                TypeIntervention::Irrigation,
                TypeIntervention::Autre,
            ]),
            'date_intervention' => fake()->dateTimeBetween('-6 months', 'now'),
            'quantite' => fake()->optional()->randomFloat(2, 1, 1000),
            'unite' => fake()->randomElement(['kg', 'L', 'h']),
            'cout' => fake()->optional()->randomFloat(2, 10, 5000),
            'description' => fake()->optional()->sentence(),
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Intervention $intervention): void {
            $parcelle = Parcelle::withoutGlobalScopes()->find($intervention->parcelle_id);

            if ($parcelle !== null) {
                $intervention->exploitation_id = $parcelle->exploitation_id;
            }
        });
    }
}
