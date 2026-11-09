<?php

namespace Database\Factories;

use App\Models\CycleCulture;
use App\Models\Exploitation;
use App\Models\IrrigationReport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IrrigationReport>
 */
class IrrigationReportFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'exploitation_id' => Exploitation::factory(),
            'campaign_id' => CycleCulture::factory(),
            'periode_debut' => now()->startOfWeek(),
            'periode_fin' => now()->endOfWeek(),
            'total_volume_m3' => 0,
            'lignes' => [],
            'genere_le' => now(),
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (IrrigationReport $report): void {
            $campaign = CycleCulture::withoutGlobalScopes()->find($report->campaign_id);

            if ($campaign !== null) {
                $report->exploitation_id = $campaign->exploitation_id;
            }
        });
    }
}
