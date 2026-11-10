<?php

namespace Database\Factories;

use App\Enums\StatutPrevisionEau;
use App\Models\WaterNeedForecast;
use App\Models\CycleCulture;
use App\Models\Exploitation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WaterNeedForecast>
 */
class WaterNeedForecastFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'exploitation_id' => Exploitation::factory(),
            'campaign_id' => CycleCulture::factory(),
            'surface_ha' => fake()->randomFloat(2, 0.5, 20),
            'duree_cycle_jours' => fake()->numberBetween(90, 150),
            'date_semis' => fake()->dateTimeBetween('-3 months', 'now'),
            'rendement_reseau_pct' => 70,
            'marge_securite_pct' => 10,
            'besoin_net_total_m3' => 100000,
            'besoin_brut_total_m3' => 142857.14,
            'volume_propose_m3' => 157142.86,
            'statut' => StatutPrevisionEau::Proposee,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (WaterNeedForecast $forecast): void {
            $campaign = CycleCulture::withoutGlobalScopes()->find($forecast->campaign_id);

            if ($campaign !== null) {
                $forecast->exploitation_id = $campaign->exploitation_id;
            }
        });
    }
}
