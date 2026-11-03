<?php

namespace Database\Factories;

use App\Enums\StatutLotStock;
use App\Models\Exploitation;
use App\Models\Intrant;
use App\Models\LotStock;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LotStock>
 */
class LotStockFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantite = fake()->randomFloat(2, 1, 5000);

        return [
            'exploitation_id' => Exploitation::factory(),
            'intrant_id' => Intrant::factory(),
            'parcelle_id' => null,
            'cycle_culture_id' => null,
            'intervention_id' => null,
            'code_lot' => 'LOT-'.fake()->unique()->numerify('######'),
            'quantite_initiale' => $quantite,
            'quantite_restante' => $quantite,
            'unite' => fake()->randomElement(['kg', 'L', 'tonnes']),
            'date_entree' => fake()->dateTimeBetween('-1 year', 'now'),
            'date_peremption' => fake()->optional()->dateTimeBetween('now', '+2 years'),
            'origine' => fake()->randomElement(['achat', 'recolte', 'transfert']),
            'prix_unitaire' => fake()->optional()->randomFloat(2, 1, 400),
            'statut' => StatutLotStock::Disponible,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (LotStock $lot): void {
            $intrant = Intrant::withoutGlobalScopes()->find($lot->intrant_id);

            if ($intrant !== null) {
                $lot->exploitation_id = $intrant->exploitation_id;
            }
        });
    }

    public function epuise(): static
    {
        return $this->state(fn (array $attributes): array => [
            'quantite_restante' => 0,
            'statut' => StatutLotStock::Epuise,
        ]);
    }
}
