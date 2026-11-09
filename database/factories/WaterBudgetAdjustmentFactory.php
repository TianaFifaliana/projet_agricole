<?php

namespace Database\Factories;

use App\Enums\StatutRallonge;
use App\Models\CampaignWaterBudget;
use App\Models\Exploitation;
use App\Models\User;
use App\Models\WaterBudgetAdjustment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WaterBudgetAdjustment>
 */
class WaterBudgetAdjustmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'exploitation_id' => Exploitation::factory(),
            'campaign_water_budget_id' => CampaignWaterBudget::factory(),
            'demande_par_id' => User::factory(),
            'valide_par_id' => null,
            'volume_supplementaire_m3' => fake()->randomFloat(2, 100, 5000),
            'motif' => fake()->sentence(),
            'statut' => StatutRallonge::EnAttente,
            'date_decision' => null,
            'commentaire_decision' => null,
        ];
    }

    public function approuvee(): static
    {
        return $this->state(fn (array $attributes): array => [
            'statut' => StatutRallonge::Approuvee,
            'date_decision' => now(),
        ]);
    }

    public function refusee(): static
    {
        return $this->state(fn (array $attributes): array => [
            'statut' => StatutRallonge::Refusee,
            'date_decision' => now(),
        ]);
    }
}
