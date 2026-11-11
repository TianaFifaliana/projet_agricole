<?php

namespace Tests\Feature;

use App\Enums\StatutCycleCulture;
use App\Enums\StatutRallonge;
use App\Models\CampaignWaterBudget;
use App\Models\Culture;
use App\Models\CycleCulture;
use App\Models\Exploitation;
use App\Models\Irrigation;
use App\Models\Parcelle;
use App\Models\User;
use App\Models\WaterBudgetAdjustment;
use App\Models\WaterSource;
use App\Services\CycleCultureService;
use App\Services\IrrigationService;
use App\Services\WaterBudgetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WaterBudgetAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_parcours_metier_creation_campagne_budget_irrigation_et_volume_restant(): void
    {
        $exploitation = Exploitation::factory()->create();
        $responsable = User::factory()->responsable()->pourExploitation($exploitation)->create();

        $parcelle = Parcelle::factory()->create([
            'exploitation_id' => $exploitation->getKey(),
            'superficie_ha' => 10,
        ]);

        $culture = Culture::factory()->create([
            'exploitation_id' => $exploitation->getKey(),
        ]);

        $campaign = app(CycleCultureService::class)->creer([
            'parcelle_id' => $parcelle->getKey(),
            'culture_id' => $culture->getKey(),
            'campagne' => '2026',
            'date_semis' => '2026-04-01',
            'date_recolte_prevue' => '2026-09-01',
            'surface_ha' => 8,
        ]);

        $this->assertInstanceOf(CycleCulture::class, $campaign);
        $this->assertSame(StatutCycleCulture::EnCours, $campaign->statut);

        $budget = app(WaterBudgetService::class)->allocateBudget($campaign, 1000);

        $this->assertSame(1000.0, (float) $budget->volume_alloue_m3);
        $this->assertSame(1000.0, $budget->volumeRestant());

        $source = WaterSource::factory()->create([
            'exploitation_id' => $exploitation->getKey(),
            'debit_m3_h' => 100,
        ]);

        $irrigation = app(IrrigationService::class)->recordIrrigation([
            'campaign_id' => $campaign->getKey(),
            'production_unit_id' => $parcelle->getKey(),
            'water_source_id' => $source->getKey(),
            'date' => now()->toDateString(),
            'duree_minutes' => 120,
            'debit_m3_h' => 50,
        ], $responsable);

        $this->assertSame(100.0, (float) $irrigation->volume_m3);

        $budget->refresh();

        $this->assertSame(100.0, (float) $budget->volume_consomme_m3);
        $this->assertSame(900.0, $budget->volumeRestant());
        $this->assertSame(1, Irrigation::withoutGlobalScopes()->count());
    }

    public function test_un_agent_ne_peut_pas_allouer_un_budget_d_eau(): void
    {
        [$agent, $campaign] = $this->contexte();

        $this->actingAs($agent)
            ->post(route('water-budgets.store'), [
                'campaign_id' => $campaign->getKey(),
                'volume_alloue_m3' => 1000,
                'seuil_alerte_pct' => 80,
            ])
            ->assertForbidden();

        $this->assertSame(0, CampaignWaterBudget::withoutGlobalScopes()->count());
    }

    public function test_un_responsable_peut_allouer_un_budget_d_eau(): void
    {
        [$agent, $campaign] = $this->contexte();
        $responsable = User::factory()->responsable()->pourExploitation($agent->exploitation)->create();

        $this->actingAs($responsable)
            ->post(route('water-budgets.store'), [
                'campaign_id' => $campaign->getKey(),
                'volume_alloue_m3' => 1000,
                'seuil_alerte_pct' => 80,
            ])
            ->assertRedirect();

        $this->assertSame(1, CampaignWaterBudget::withoutGlobalScopes()->count());
    }

    public function test_un_agent_ne_peut_pas_modifier_un_budget_en_approuvant_une_rallonge(): void
    {
        [$agent, $campaign] = $this->contexte();
        $budget = app(WaterBudgetService::class)->allocateBudget($campaign, 1000);

        $adjustment = WaterBudgetAdjustment::factory()->create([
            'exploitation_id' => $agent->exploitation_id,
            'campaign_water_budget_id' => $budget->getKey(),
            'demande_par_id' => $agent->getKey(),
            'statut' => StatutRallonge::EnAttente,
        ]);

        $this->actingAs($agent)
            ->post(route('water-budgets.adjustments.approve', $adjustment), ['commentaire' => 'ok'])
            ->assertForbidden();

        $this->assertSame(StatutRallonge::EnAttente, $adjustment->fresh()->statut);
        $this->assertSame(1000.0, (float) $budget->fresh()->volume_alloue_m3);
    }

    public function test_un_utilisateur_ne_voit_pas_les_budgets_d_une_autre_exploitation(): void
    {
        [$userA, $campaignA] = $this->contexte();
        $budgetA = app(WaterBudgetService::class)->allocateBudget($campaignA, 1000);
        $codeA = $budgetA->campaign->parcelle->code;

        $exploitationB = Exploitation::factory()->create();
        $campaignB = $this->campagnePour($exploitationB, 'RZ-B');
        $budgetB = app(WaterBudgetService::class)->allocateBudget($campaignB, 5000);
        $codeB = $budgetB->campaign->parcelle->code;

        $this->actingAs($userA)
            ->get(route('water-budgets.index'))
            ->assertOk()
            ->assertSee($codeA)
            ->assertDontSee($codeB);
    }

    public function test_un_utilisateur_ne_peut_pas_allouer_un_budget_a_une_campagne_d_une_autre_exploitation(): void
    {
        [$agentA] = $this->contexte();
        $userA = User::factory()->responsable()->pourExploitation($agentA->exploitation)->create();

        $exploitationB = Exploitation::factory()->create();
        $campaignB = $this->campagnePour($exploitationB, 'RZ-B');

        $this->actingAs($userA)
            ->post(route('water-budgets.store'), [
                'campaign_id' => $campaignB->getKey(),
                'volume_alloue_m3' => 1000,
            ])
            ->assertSessionHasErrors('campaign_id');

        $this->assertSame(0, CampaignWaterBudget::withoutGlobalScopes()->count());
    }

    public function test_un_responsable_ne_peut_pas_approuver_une_rallonge_d_une_autre_exploitation(): void
    {
        [$agentA] = $this->contexte();
        $userA = User::factory()->responsable()->pourExploitation($agentA->exploitation)->create();

        $exploitationB = Exploitation::factory()->create();
        $campaignB = $this->campagnePour($exploitationB, 'RZ-B');
        $budgetB = app(WaterBudgetService::class)->allocateBudget($campaignB, 1000);

        $adjustmentB = WaterBudgetAdjustment::factory()->create([
            'exploitation_id' => $exploitationB->getKey(),
            'campaign_water_budget_id' => $budgetB->getKey(),
            'statut' => StatutRallonge::EnAttente,
        ]);

        $this->actingAs($userA)
            ->post(route('water-budgets.adjustments.approve', $adjustmentB))
            ->assertNotFound();

        $this->assertSame(StatutRallonge::EnAttente, $adjustmentB->fresh()->statut);
    }

    /**
     * @return array{0: User, 1: CycleCulture}
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

        return [$user, $campaign];
    }

    private function campagnePour(Exploitation $exploitation, string $codeParcelle): CycleCulture
    {
        $parcelle = Parcelle::factory()->create([
            'exploitation_id' => $exploitation->getKey(),
            'code' => $codeParcelle,
            'superficie_ha' => 10,
        ]);

        $culture = Culture::factory()->create([
            'exploitation_id' => $exploitation->getKey(),
        ]);

        return CycleCulture::factory()->create([
            'exploitation_id' => $exploitation->getKey(),
            'parcelle_id' => $parcelle->getKey(),
            'culture_id' => $culture->getKey(),
            'statut' => StatutCycleCulture::EnCours,
        ]);
    }
}
