<?php

namespace App\Exceptions;

use App\Models\CampaignWaterBudget;

class WaterQuotaExceededException extends RegleMetierException
{
    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'quota_eau_depasse';
    }

    public static function pour(CampaignWaterBudget $budget, float $volumeDemande): self
    {
        return new self(sprintf(
            "Quota d'eau dépassé pour la campagne #%d : %s m³ demandés, %s m³ restants.",
            $budget->campaign_id,
            number_format($volumeDemande, 2, ',', ' '),
            number_format($budget->volumeRestant(), 2, ',', ' '),
        ));
    }
}
