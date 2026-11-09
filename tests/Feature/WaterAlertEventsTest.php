<?php

namespace Tests\Feature;

use App\Enums\StatutCycleCulture;
use App\Events\IrrigationRecorded;
use App\Events\WaterQuotaCritical;
use App\Events\WaterQuotaExhausted;
use App\Jobs\GenerateIrrigationReportJob;
use App\Listeners\InvalidateWaterDashboardCache;
use App\Listeners\LogWaterAlert;
use App\Listeners\NotifyManagerOfWaterAlert;
use App\Models\Culture;
use App\Models\CycleCulture;
use App\Models\Exploitation;
use App\Models\Irrigation;
use App\Models\IrrigationReport;
use App\Models\Parcelle;
use App\Models\User;
use App\Models\WaterSource;
use App\Notifications\WaterQuotaAlertNotification;
use App\Services\DashboardService;
use App\Services\IrrigationService;
use App\Services\WaterBudgetService;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WaterAlertEventsTest extends TestCase
{
    use RefreshDatabase;

    public function test_une_irrigation_au_seuil_declenche_les_evenements_attendus(): void
    {
        [$user, $campaign, $parcelle, $source] = $this->contexte();
        $budget = app(WaterBudgetService::class)->allocateBudget($campaign, 100, 80);

        Event::fake([WaterQuotaCritical::class, WaterQuotaExhausted::class, IrrigationRecorded::class]);

        $irrigation = app(IrrigationService::class)->recordIrrigation(
            $this->donneesIrrigation($campaign, $parcelle, $source, 240, 20),
            $user,
        );

        Event::assertDispatched(IrrigationRecorded::class, fn (IrrigationRecorded $event): bool => $event->irrigation->is($irrigation));
        Event::assertDispatched(WaterQuotaCritical::class, fn (WaterQuotaCritical $event): bool => $event->budget->is($budget));
        Event::assertNotDispatched(WaterQuotaExhausted::class);
    }

    public function test_une_irrigation_qui_epuise_le_quota_declenche_l_evenement_epuise(): void
    {
        [$user, $campaign, $parcelle, $source] = $this->contexte();
        $budget = app(WaterBudgetService::class)->allocateBudget($campaign, 100, 80);

        Event::fake([WaterQuotaCritical::class, WaterQuotaExhausted::class, IrrigationRecorded::class]);

        app(IrrigationService::class)->recordIrrigation(
            $this->donneesIrrigation($campaign, $parcelle, $source, 300, 20),
            $user,
        );

        Event::assertDispatched(WaterQuotaExhausted::class, fn (WaterQuotaExhausted $event): bool => $event->budget->is($budget));
        Event::assertNotDispatched(WaterQuotaCritical::class);
    }

    public function test_le_listener_de_notification_est_mis_en_file(): void
    {
        [, $campaign] = $this->contexte();
        $budget = app(WaterBudgetService::class)->allocateBudget($campaign, 100, 80);

        Queue::fake();

        event(new WaterQuotaCritical($budget));

        Queue::assertPushed(
            CallQueuedListener::class,
            fn (CallQueuedListener $job): bool => $job->class === NotifyManagerOfWaterAlert::class,
        );
    }

    public function test_la_notification_utilise_les_canaux_database_et_mail(): void
    {
        [$user, $campaign] = $this->contexte();
        $responsable = User::factory()->responsable()->pourExploitation($user->exploitation)->create();
        $budget = app(WaterBudgetService::class)->allocateBudget($campaign, 100, 80);

        $budget->forceFill(['volume_consomme_m3' => 85])->save();

        Notification::fake();

        app(NotifyManagerOfWaterAlert::class)->handleCritical(new WaterQuotaCritical($budget));

        Notification::assertSentTo(
            $responsable,
            WaterQuotaAlertNotification::class,
            fn (WaterQuotaAlertNotification $notification, array $channels): bool => $budget->is($notification->budget)
                && in_array('database', $channels, true)
                && in_array('mail', $channels, true),
        );
    }

    public function test_le_message_de_notification_contient_la_campagne_le_pourcentage_et_le_volume_restant(): void
    {
        [$user, $campaign] = $this->contexte();

        $campaign->culture->forceFill(['nom' => 'Riz'])->save();
        $campaign->forceFill(['campagne' => '2026'])->save();

        $budget = app(WaterBudgetService::class)->allocateBudget($campaign, 100, 80);
        $budget->forceFill(['volume_consomme_m3' => 85])->save();
        $budget->load(['campaign.culture', 'campaign.parcelle']);

        $data = (new WaterQuotaAlertNotification($budget))->toArray($user);

        $this->assertSame(
            "Alerte : la campagne Riz 2026 a consommé 85 % de son quota d'eau (15,00 m³ restants).",
            $data['message'],
        );
    }

    public function test_les_evenements_invalident_le_cache_du_tableau_de_bord(): void
    {
        [$user, $campaign, $parcelle, $source] = $this->contexte();
        $budget = app(WaterBudgetService::class)->allocateBudget($campaign, 100, 80);
        $cacheKey = DashboardService::cacheKey($user->exploitation_id);

        Cache::put($cacheKey, ['statistiques' => true], 60);
        app(InvalidateWaterDashboardCache::class)->handleCritical(new WaterQuotaCritical($budget));
        $this->assertFalse(Cache::has($cacheKey));

        $irrigation = $this->irrigation($campaign, $parcelle, $source, '2026-05-04', 10);

        Cache::put($cacheKey, ['statistiques' => true], 60);
        app(InvalidateWaterDashboardCache::class)->handleIrrigationRecorded(new IrrigationRecorded($irrigation));
        $this->assertFalse(Cache::has($cacheKey));
    }

    public function test_l_alerte_est_journalisee_sur_le_canal_audit(): void
    {
        [, $campaign] = $this->contexte();
        $budget = app(WaterBudgetService::class)->allocateBudget($campaign, 100, 80);

        Log::spy();
        Log::shouldReceive('channel')->with('audit')->andReturnSelf();

        app(LogWaterAlert::class)->handleCritical(new WaterQuotaCritical($budget));

        Log::shouldHaveReceived('channel')->with('audit')->once();
    }

    public function test_le_job_de_rapport_peut_etre_mis_en_file(): void
    {
        Queue::fake();

        GenerateIrrigationReportJob::dispatch(42);

        Queue::assertPushed(
            GenerateIrrigationReportJob::class,
            fn (GenerateIrrigationReportJob $job): bool => $job->campaignId === 42,
        );
    }

    public function test_le_job_de_rapport_calcule_la_consommation_par_riziere_et_par_semaine(): void
    {
        [$user, $campaign, $parcelle, $source] = $this->contexte();
        $autreParcelle = Parcelle::factory()->create([
            'exploitation_id' => $user->exploitation_id,
            'code' => 'RZ-02',
        ]);

        $this->irrigation($campaign, $parcelle, $source, '2026-05-04', 80);
        $this->irrigation($campaign, $parcelle, $source, '2026-05-06', 20);
        $this->irrigation($campaign, $autreParcelle, $source, '2026-05-12', 50);
        $this->irrigation($campaign, $parcelle, $source, '2026-05-13', 30);

        (new GenerateIrrigationReportJob($campaign->getKey()))->handle();

        $rapport = IrrigationReport::withoutGlobalScopes()->sole();

        $this->assertSame($campaign->getKey(), $rapport->campaign_id);
        $this->assertSame(180.0, (float) $rapport->total_volume_m3);
        $this->assertSame('2026-05-04', $rapport->periode_debut?->toDateString());
        $this->assertSame('2026-05-13', $rapport->periode_fin?->toDateString());
        $this->assertCount(3, $rapport->lignes);

        $this->assertSame('2026-W19', $rapport->lignes[0]['semaine']);
        $this->assertSame('RZ-01', $rapport->lignes[0]['parcelle_code']);
        $this->assertSame(100.0, (float) $rapport->lignes[0]['volume_m3']);
        $this->assertSame(2, $rapport->lignes[0]['nombre_irrigations']);

        $this->assertSame('2026-W20', $rapport->lignes[1]['semaine']);
        $this->assertSame('RZ-01', $rapport->lignes[1]['parcelle_code']);
        $this->assertSame(30.0, (float) $rapport->lignes[1]['volume_m3']);

        $this->assertSame('2026-W20', $rapport->lignes[2]['semaine']);
        $this->assertSame('RZ-02', $rapport->lignes[2]['parcelle_code']);
        $this->assertSame(50.0, (float) $rapport->lignes[2]['volume_m3']);
    }

    /**
     * @return array{0: User, 1: CycleCulture, 2: Parcelle, 3: WaterSource}
     */
    private function contexte(): array
    {
        $exploitation = Exploitation::factory()->create();
        $user = User::factory()->agent()->pourExploitation($exploitation)->create();

        $parcelle = Parcelle::factory()->create([
            'exploitation_id' => $exploitation->getKey(),
            'code' => 'RZ-01',
            'superficie_ha' => 10,
        ]);

        $culture = Culture::factory()->create([
            'exploitation_id' => $exploitation->getKey(),
        ]);

        $campaign = CycleCulture::factory()->create([
            'exploitation_id' => $exploitation->getKey(),
            'parcelle_id' => $parcelle->getKey(),
            'culture_id' => $culture->getKey(),
            'statut' => StatutCycleCulture::EnCours,
        ]);

        $source = WaterSource::factory()->create([
            'exploitation_id' => $exploitation->getKey(),
            'debit_m3_h' => 100,
        ]);

        return [$user, $campaign, $parcelle, $source];
    }

    /**
     * @return array<string, mixed>
     */
    private function donneesIrrigation(
        CycleCulture $campaign,
        Parcelle $parcelle,
        WaterSource $source,
        int $dureeMinutes,
        float $debit,
    ): array {
        return [
            'campaign_id' => $campaign->getKey(),
            'production_unit_id' => $parcelle->getKey(),
            'water_source_id' => $source->getKey(),
            'date' => now()->toDateString(),
            'duree_minutes' => $dureeMinutes,
            'debit_m3_h' => $debit,
        ];
    }

    private function irrigation(
        CycleCulture $campaign,
        Parcelle $parcelle,
        WaterSource $source,
        string $date,
        float $volume,
    ): Irrigation {
        return Irrigation::withoutGlobalScopes()->create([
            'exploitation_id' => $campaign->exploitation_id,
            'campaign_id' => $campaign->getKey(),
            'production_unit_id' => $parcelle->getKey(),
            'water_source_id' => $source->getKey(),
            'user_id' => null,
            'date' => $date,
            'duree_minutes' => 60,
            'debit_m3_h' => $volume,
            'volume_m3' => $volume,
        ]);
    }
}
