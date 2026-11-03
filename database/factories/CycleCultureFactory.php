<?php

namespace Database\Factories;

use App\Enums\StatutCycleCulture;
use App\Models\Culture;
use App\Models\CycleCulture;
use App\Models\Exploitation;
use App\Models\Parcelle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CycleCulture>
 */
class CycleCultureFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $semis = fake()->dateTimeBetween('-6 months', 'now');

        return [
            'exploitation_id' => Exploitation::factory(),
            'parcelle_id' => Parcelle::factory(),
            'culture_id' => Culture::factory(),
            'campagne' => $semis->format('Y'),
            'date_semis' => $semis,
            'date_recolte_prevue' => fake()->dateTimeBetween('now', '+4 months'),
            'date_recolte_reelle' => null,
            'statut' => StatutCycleCulture::EnCours,
            'surface_ha' => fake()->randomFloat(2, 0.5, 20),
            'rendement_obtenu_tha' => null,
            'quantite_recoltee_kg' => null,
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (CycleCulture $cycle): void {
            $parcelle = Parcelle::withoutGlobalScopes()->find($cycle->parcelle_id);

            if ($parcelle !== null) {
                $cycle->exploitation_id = $parcelle->exploitation_id;
            }
        });
    }

    public function recolte(): static
    {
        return $this->state(fn (array $attributes): array => [
            'statut' => StatutCycleCulture::Recolte,
            'date_recolte_reelle' => now()->subDays(10),
            'quantite_recoltee_kg' => fake()->randomFloat(2, 1000, 50000),
            'rendement_obtenu_tha' => fake()->randomFloat(2, 2, 10),
        ]);
    }
}
