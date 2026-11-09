<?php

namespace App\Listeners;

use App\Events\IrrigationRecorded;
use App\Events\WaterQuotaCritical;
use App\Events\WaterQuotaExhausted;
use App\Services\DashboardService;

class InvalidateWaterDashboardCache
{
    public function __construct(private DashboardService $dashboard) {}

    public function handleCritical(WaterQuotaCritical $event): void
    {
        $this->dashboard->invalider($event->budget->exploitation_id);
    }

    public function handleExhausted(WaterQuotaExhausted $event): void
    {
        $this->dashboard->invalider($event->budget->exploitation_id);
    }

    public function handleIrrigationRecorded(IrrigationRecorded $event): void
    {
        $this->dashboard->invalider($event->irrigation->exploitation_id);
    }
}
