<?php

namespace Database\Factories;

use App\Enums\PhaseCulturale;
use App\Models\WaterNeedForecast;
use App\Models\WaterNeedForecastLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WaterNeedForecastLine>
 */
class WaterNeedForecastLineFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $debut = fake()->dateTimeBetween('-3 months', 'now');
        $duree = fake()->numberBetween(15, 45);

        return [
            'water_need_forecast_id' => WaterNeedForecast::factory(),
            'phase' => fake()->randomElement(PhaseCulturale::cases()),
            'date_debut' => $debut,
            'date_fin' => (clone $debut)->modify(sprintf('+%d days', $duree - 1)),
            'duree_jours' => $duree,
            'besoin_journalier_mm' => fake()->randomFloat(2, 5, 12),
            'besoin_net_m3' => fake()->randomFloat(2, 5000, 40000),
            'besoin_brut_m3' => fake()->randomFloat(2, 7000, 60000),
        ];
    }
}
