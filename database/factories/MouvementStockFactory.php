<?php

namespace Database\Factories;

use App\Enums\TypeMouvementStock;
use App\Models\LotStock;
use App\Models\MouvementStock;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MouvementStock>
 */
class MouvementStockFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lot_stock_id' => LotStock::factory(),
            'vente_id' => null,
            'intervention_id' => null,
            'user_id' => null,
            'type' => TypeMouvementStock::Entree,
            'quantite' => 100,
            'quantite_avant' => 0,
            'quantite_apres' => 100,
            'motif' => 'Entrée de stock',
            'reference' => fake()->optional()->bothify('REF-####'),
            'date_mouvement' => now(),
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (MouvementStock $mouvement): void {
            $lot = LotStock::withoutGlobalScopes()->find($mouvement->lot_stock_id);

            if ($lot !== null) {
                $mouvement->exploitation_id = $lot->exploitation_id;
            }
        });
    }
}
