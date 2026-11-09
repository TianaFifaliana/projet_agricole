<?php

namespace Tests\Feature;

use App\Enums\CategorieDepense;
use App\Enums\EtatSourceEau;
use App\Enums\StatutBudgetEau;
use App\Enums\StatutCycleCulture;
use App\Exceptions\RegleMetierException;
use App\Exceptions\WaterQuotaExceededException;
use App\Models\CampaignWaterBudget;
use App\Models\Culture;
use App\Models\CycleCulture;
use App\Models\Depense;
use App\Models\Exploitation;
use App\Models\Irrigation;
use App\Models\Parcelle;
use App\Models\User;
use App\Models\WaterSource;
use App\Services\FinanceService;
use App\Services\IrrigationService;
use App\Services\WaterBudgetService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Tests\TestCase;

class IrrigationQuotaTest extends TestCase
{
    use RefreshDatabase;

    public function test_une_irrigation_qui_depasse_le_quota_est_refusee_et_non_enregistree(): void
    {
        [$user, $campaign, $parcelle, $source] = $this->contexte();

        app(WaterBudgetService::class)->allocateBudget($campaign, 100);

        $irrigations = app(IrrigationService::class);

        $irrigations->recordIrrigation([
            'campaign_id' => $campaign->getKey(),
            'production_unit_id' => $parcelle->getKey(),
            'water_source_id' => $source->getKey(),
            'date' => now()->toDateString(),
            'duree_minutes' => 240,
            'debit_m3_h' => 20,
        ], $user);

        $this->assertSame(1, Irrigation::withoutGlobalScopes()->count());
        $this->assertSame(80.0, (float) CampaignWaterBudget::withoutGlobalScopes()->first()->volume_consomme_m3);

        try {
            $irrigations->recordIrrigation([
                'campaign_id' => $campaign->getKey(),
                'production_unit_id' => $parcelle->getKey(),
                'water_source_id' => $source->getKey(),
                'date' => now()->toDateString(),
                'duree_minutes' => 90,
                'debit_m3_h' => 20,
            ], $user);

            $this->fail('Une irrigation dépassant le quota aurait dû être refusée.');
        } catch (WaterQuotaExceededException) {
            $this->assertSame(1, Irrigation::withoutGlobalScopes()->count());
            $this->assertSame(80.0, (float) CampaignWaterBudget::withoutGlobalScopes()->first()->volume_consomme_m3);
        }
    }

    public function test_une_irrigation_valide_consomme_le_quota_et_passe_le_budget_en_alerte(): void
    {
        [$user, $campaign, $parcelle, $source] = $this->contexte();

        app(WaterBudgetService::class)->allocateBudget($campaign, 100, 80);

        $irrigation = app(IrrigationService::class)->recordIrrigation([
            'campaign_id' => $campaign->getKey(),
            'production_unit_id' => $parcelle->getKey(),
            'water_source_id' => $source->getKey(),
            'date' => now()->toDateString(),
            'duree_minutes' => 240,
            'debit_m3_h' => 20,
        ], $user);

        $this->assertSame(80.0, (float) $irrigation->volume_m3);

        $budget = CampaignWaterBudget::withoutGlobalScopes()->first();

        $this->assertSame(80.0, (float) $budget->volume_consomme_m3);
        $this->assertSame(20.0, $budget->volumeRestant());
        $this->assertSame(80.0, $budget->pourcentageConsomme());
        $this->assertSame(StatutBudgetEau::Alerte, $budget->statut);
    }

    public function test_un_agent_ne_peut_pas_allouer_le_budget_mais_peut_irriguer(): void
    {
        [$agent, $campaign, $parcelle, $source] = $this->contexte();

        $responsable = User::factory()->responsable()->pourExploitation($agent->exploitation)->create();

        $this->assertFalse($agent->can('create', CampaignWaterBudget::class));
        $this->assertTrue($responsable->can('create', CampaignWaterBudget::class));
        $this->assertTrue($agent->can('create', Irrigation::class));

        app(WaterBudgetService::class)->allocateBudget($campaign, 100);

        $this->actingAs($agent)->post(route('irrigations.store'), [
            'campaign_id' => $campaign->getKey(),
            'production_unit_id' => $parcelle->getKey(),
            'water_source_id' => $source->getKey(),
            'date' => now()->toDateString(),
            'duree_minutes' => 60,
            'debit_m3_h' => 10,
        ])->assertRedirect(route('irrigations.index'));

        $this->assertSame(1, Irrigation::withoutGlobalScopes()->count());
    }

    public function test_une_source_en_panne_refuse_l_irrigation(): void
    {
        [$user, $campaign, $parcelle, $source] = $this->contexte();

        $source->forceFill(['etat' => EtatSourceEau::EnPanne])->save();

        app(WaterBudgetService::class)->allocateBudget($campaign, 100);

        $this->expectException(RegleMetierException::class);

        app(IrrigationService::class)->recordIrrigation([
            'campaign_id' => $campaign->getKey(),
            'production_unit_id' => $parcelle->getKey(),
            'water_source_id' => $source->getKey(),
            'date' => now()->toDateString(),
            'duree_minutes' => 60,
            'debit_m3_h' => 10,
        ], $user);
    }

    public function test_les_pages_irrigation_et_quotas_s_affichent(): void
    {
        [$user] = $this->contexte();

        $this->actingAs($user)->get(route('irrigations.index'))->assertOk();
        $this->actingAs($user)->get(route('irrigations.create'))->assertOk();
        $this->actingAs($user)->get(route('water-budgets.index'))->assertOk();
    }

    public function test_une_irrigation_valide_cree_une_depense_automatique(): void
    {
        [$user, $campaign, $parcelle, $source] = $this->contexte();

        app(WaterBudgetService::class)->allocateBudget($campaign, 100);

        $irrigation = app(IrrigationService::class)->recordIrrigation(
            $this->donneesIrrigation($campaign, $parcelle, $source),
            $user,
        );

        $depense = Depense::withoutGlobalScopes()->sole();

        $this->assertSame($irrigation->getKey(), $depense->irrigation_id);
        $this->assertSame(CategorieDepense::Irrigation, $depense->categorie);
        $this->assertSame(69.0, (float) $depense->montant);
    }

    public function test_une_irrigation_est_journalisee_dans_le_canal_audit(): void
    {
        [$user, $campaign, $parcelle, $source] = $this->contexte();

        app(WaterBudgetService::class)->allocateBudget($campaign, 100);

        Log::spy();
        Log::shouldReceive('channel')->with('audit')->andReturnSelf();

        app(IrrigationService::class)->recordIrrigation(
            $this->donneesIrrigation($campaign, $parcelle, $source),
            $user,
        );

        Log::shouldHaveReceived('channel')->with('audit')->once();
    }

    public function test_la_transaction_est_annulee_si_la_creation_de_la_depense_echoue(): void
    {
        [$user, $campaign, $parcelle, $source] = $this->contexte();

        app(WaterBudgetService::class)->allocateBudget($campaign, 100);

        $this->mock(FinanceService::class, function ($mock): void {
            $mock->shouldReceive('enregistrerDepenseIrrigation')
                ->once()
                ->andThrow(new RuntimeException('Panne du service financier.'));
        });

        try {
            app(IrrigationService::class)->recordIrrigation(
                $this->donneesIrrigation($campaign, $parcelle, $source),
                $user,
            );

            $this->fail('La panne du service financier aurait dû annuler la transaction.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Panne du service financier.', $exception->getMessage());
        }

        $this->assertSame(0, Irrigation::withoutGlobalScopes()->count());
        $this->assertSame(0, Depense::withoutGlobalScopes()->count());
        $this->assertSame(0.0, (float) CampaignWaterBudget::withoutGlobalScopes()->sole()->volume_consomme_m3);
    }

    public function test_scenario_de_concurrence_deux_agents_ne_peuvent_pas_depasser_le_quota(): void
    {
        [$agentA, $campaign, $parcelle, $source] = $this->contexte();

        $agentB = User::factory()->agent()->pourExploitation($agentA->exploitation)->create();

        app(WaterBudgetService::class)->allocateBudget($campaign, 100);

        $snapshot = CampaignWaterBudget::withoutGlobalScopes()->sole();

        $this->assertSame(100.0, $snapshot->volumeRestant());

        $service = app(IrrigationService::class);

        $service->recordIrrigation(
            $this->donneesIrrigation($campaign, $parcelle, $source),
            $agentA,
        );

        try {
            $service->recordIrrigation(
                $this->donneesIrrigation($campaign, $parcelle, $source),
                $agentB,
            );

            $this->fail('Le second agent aurait dû être bloqué par le quota.');
        } catch (WaterQuotaExceededException $exception) {
            $this->assertStringContainsString('Quota', $exception->getMessage());
        }

        $budget = CampaignWaterBudget::withoutGlobalScopes()->sole();

        $this->assertSame(60.0, (float) $budget->volume_consomme_m3);
        $this->assertSame(1, Irrigation::withoutGlobalScopes()->count());
        $this->assertSame(1, Depense::withoutGlobalScopes()->count());
    }

    public function test_la_contrainte_de_securite_empeche_de_depasser_le_quota_en_base(): void
    {
        [$user, $campaign] = $this->contexte();

        $budget = app(WaterBudgetService::class)->allocateBudget($campaign, 100);

        $this->expectException(QueryException::class);

        DB::table('campaign_water_budgets')
            ->where('id', $budget->getKey())
            ->update(['volume_consomme_m3' => 150]);
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
        int $dureeMinutes = 180,
        float $debit = 20,
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
}
