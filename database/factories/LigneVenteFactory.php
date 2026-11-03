<?php

namespace Database\Factories;

use App\Models\LigneVente;
use App\Models\Vente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LigneVente>
 */
class LigneVenteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantite = fake()->randomFloat(2, 1, 500);
        $prix = fake()->randomFloat(2, 1, 200);

        return [
            'vente_id' => Vente::factory(),
            'lot_stock_id' => null,
            'designation' => fake()->words(3, true),
            'quantite' => $quantite,
            'unite' => fake()->randomElement(['kg', 'L', 'tonnes']),
            'prix_unitaire' => $prix,
            'montant' => round($quantite * $prix, 2),
        ];
    }
}
