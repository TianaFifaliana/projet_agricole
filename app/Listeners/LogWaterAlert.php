<?php

namespace App\Listeners;

use App\Events\WaterQuotaCritical;
use App\Events\WaterQuotaExhausted;
use App\Models\CampaignWaterBudget;
use Illuminate\Support\Facades\Log;

class LogWaterAlert
{
    public function handleCritical(WaterQuotaCritical $event): void
    {
        $this->journaliser('water.quota.critical', $event->budget);
    }

    public function handleExhausted(WaterQuotaExhausted $event): void
    {
        $this->journaliser('water.quota.exhausted', $event->budget);
    }

    private function journaliser(string $action, CampaignWaterBudget $budget): void
    {
        Log::channel('audit')->warning($action, [
            'budget_id' => $budget->getKey(),
            'exploitation_id' => $budget->exploitation_id,
            'campaign_id' => $budget->campaign_id,
            'volume_alloue_m3' => (float) $budget->volume_alloue_m3,
            'volume_consomme_m3' => (float) $budget->volume_consomme_m3,
            'volume_restant_m3' => $budget->volumeRestant(),
            'pourcentage_consomme' => $budget->pourcentageConsomme(),
            'seuil_alerte_pct' => (float) $budget->seuil_alerte_pct,
            'statut' => $budget->statut->value,
        ]);
    }
}
