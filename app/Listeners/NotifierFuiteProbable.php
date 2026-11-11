<?php

namespace App\Listeners;

use App\Enums\UserRole;
use App\Events\FuiteProbableDetectee;
use App\Models\ReleveNiveauEau;
use App\Models\Scopes\ExploitationScope;
use App\Models\User;
use App\Notifications\FuiteEauDetecteeNotification;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

class NotifierFuiteProbable implements ShouldQueueAfterCommit
{
    use InteractsWithQueue;

    public int $tries = 3;

    public int $timeout = 60;

    /**
     * @var array<int, int>
     */
    public array $backoff = [10, 60, 300];

    public function handle(FuiteProbableDetectee $event): void
    {
        $releve = ReleveNiveauEau::withoutGlobalScope(ExploitationScope::class)
            ->with(['campaign.culture', 'campaign.parcelle', 'parcelle'])
            ->find($event->releve->getKey());

        if (! $releve instanceof ReleveNiveauEau) {
            return;
        }

        $responsables = User::query()
            ->where('exploitation_id', $releve->exploitation_id)
            ->where('role', UserRole::Responsable->value)
            ->where('actif', true)
            ->get();

        if ($responsables->isEmpty()) {
            return;
        }

        Notification::send(
            $responsables,
            new FuiteEauDetecteeNotification($releve),
        );
    }
}
