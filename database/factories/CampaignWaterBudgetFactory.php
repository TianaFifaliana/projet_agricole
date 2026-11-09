<?php

namespace Database\Factories;

use App\Enums\StatutBudgetEau;
use App\Models\CampaignWaterBudget;
use App\Models\CycleCulture;
use App\Models\Exploitation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CampaignWaterBudget>
 */
class CampaignWaterBudgetFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'exploitation_id' => Exploitation::factory(),
            'campaign_id' => CycleCulture::factory(),
            'volume_alloue_m3' => fake()->randomFloat(2, 1000, 20000),
            'volume_consomme_m3' => 0,
            'seuil_alerte_pct' => 80,
            'statut' => StatutBudgetEau::Actif,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (CampaignWaterBudget $budget): void {
            $campaign = CycleCulture::withoutGlobalScopes()->find($budget->campaign_id);

            if ($campaign !== null) {
                $budget->exploitation_id = $campaign->exploitation_id;
            }
        });
    }
}
