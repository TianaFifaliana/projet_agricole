<?php

namespace App\Listeners;

use App\Enums\UserRole;
use App\Events\WaterQuotaCritical;
use App\Events\WaterQuotaExhausted;
use App\Models\CampaignWaterBudget;
use App\Models\Scopes\ExploitationScope;
use App\Models\User;
use App\Notifications\WaterQuotaAlertNotification;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

class NotifyManagerOfWaterAlert implements ShouldQueueAfterCommit
{
    use InteractsWithQueue;

    public int $tries = 3;

    public int $timeout = 60;

    /**
     * @var array<int, int>
     */
    public array $backoff = [10, 60, 300];

    public function handleCritical(WaterQuotaCritical $event): void
    {
        $this->notifier($event->budget, quotaEpuise: false);
    }

    public function handleExhausted(WaterQuotaExhausted $event): void
    {
        $this->notifier($event->budget, quotaEpuise: true);
    }

    private function notifier(CampaignWaterBudget $budget, bool $quotaEpuise): void
    {
        $budget = CampaignWaterBudget::withoutGlobalScope(ExploitationScope::class)
            ->with(['campaign.culture', 'campaign.parcelle'])
            ->find($budget->getKey());

        if (! $budget instanceof CampaignWaterBudget) {
            return;
        }

        $responsables = User::query()
            ->where('exploitation_id', $budget->exploitation_id)
            ->where('role', UserRole::Responsable->value)
            ->where('actif', true)
            ->get();

        if ($responsables->isEmpty()) {
            return;
        }

        Notification::send(
            $responsables,
            new WaterQuotaAlertNotification($budget, $quotaEpuise),
        );
    }
}
