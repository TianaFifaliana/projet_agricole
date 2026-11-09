<?php

namespace Database\Factories;

use App\Enums\CategorieDepense;
use App\Models\Depense;
use App\Models\Exploitation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Depense>
 */
class DepenseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'exploitation_id' => Exploitation::factory(),
            'irrigation_id' => null,
            'user_id' => null,
            'date' => now()->toDateString(),
            'categorie' => CategorieDepense::Autre,
            'libelle' => fake()->sentence(3),
            'montant' => fake()->randomFloat(2, 10, 500),
            'notes' => null,
        ];
    }
}
