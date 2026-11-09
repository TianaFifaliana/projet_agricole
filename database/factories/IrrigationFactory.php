<?php

namespace Database\Factories;

use App\Models\CycleCulture;
use App\Models\Exploitation;
use App\Models\Irrigation;
use App\Models\Parcelle;
use App\Models\User;
use App\Models\WaterSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Irrigation>
 */
class IrrigationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'exploitation_id' => Exploitation::factory(),
            'campaign_id' => CycleCulture::factory(),
            'production_unit_id' => Parcelle::factory(),
            'water_source_id' => WaterSource::factory(),
            'user_id' => User::factory(),
            'date' => now()->toDateString(),
            'duree_minutes' => 60,
            'debit_m3_h' => 10,
            'volume_m3' => 10,
            'cout' => null,
            'observation' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Irrigation $irrigation): void {
            $campaign = CycleCulture::withoutGlobalScopes()->find($irrigation->campaign_id);

            if ($campaign !== null) {
                $irrigation->exploitation_id = $campaign->exploitation_id;
                $irrigation->production_unit_id = $campaign->parcelle_id;
            }
        });
    }
}
