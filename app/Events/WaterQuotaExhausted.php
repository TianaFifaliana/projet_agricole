<?php

namespace App\Events;

use App\Models\CampaignWaterBudget;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WaterQuotaExhausted
{
    use Dispatchable, SerializesModels;

    public function __construct(public CampaignWaterBudget $budget) {}
}
