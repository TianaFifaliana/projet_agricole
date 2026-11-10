<?php

namespace Tests\Feature;

use App\Enums\StatutCycleCulture;
use App\Models\CampaignWaterBudget;
use App\Models\Culture;
use App\Models\CycleCulture;
use App\Models\Depense;
use App\Models\Exploitation;
use App\Models\Irrigation;
use App\Models\Parcelle;
use App\Models\User;
use App\Models\WaterSource;
use App\Services\IrrigationService;
use App\Services\WaterBudgetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiCampaignIrrigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_api_login_renvoie_un_token_utilisable(): void
    {
        [$user, $campaign] = $this->contexte();

        $login = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'phpunit',
        ]);

        $login->assertOk()
            ->assertJsonStructure(['token', 'token_type', 'user' => ['id', 'role', 'exploitation_id']]);

        $this->withToken($login->json('token'))
            ->getJson('/api/v1/campaigns')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $campaign->getKey());
    }

    public function test_les_routes_campaigns_exigent_un_token(): void
    {
        [$user, $campaign] = $this->contexte();

        $this->getJson('/api/v1/campaigns')->assertUnauthorized();
        $this->getJson("/api/v1/campaigns/{$campaign->getKey()}")->assertUnauthorized();
        $this->getJson("/api/v1/campaigns/{$campaign->getKey()}/irrigations")->assertUnauthorized();
        $this->postJson("/api/v1/campaigns/{$campaign->getKey()}/irrigations", [])
            ->assertUnauthorized();
    }

    public function test_la_liste_des_campagnes_inclut_le_quota_d_eau_restant(): void
    {
        [$user, $campaign, $parcelle, $source] = $this->contexte();

        app(WaterBudgetService::class)->allocateBudget($campaign, 1000);

        app(IrrigationService::class)->recordIrrigation([
            'campaign_id' => $campaign->getKey(),
            'production_unit_id' => $parcelle->getKey(),
            'water_source_id' => $source->getKey(),
            'date' => now()->toDateString(),
            'duree_minutes' => 120,
            'debit_m3_h' => 50,
        ], $user);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/campaigns')
            ->assertOk()
            ->assertJsonPath('data.0.quota_eau.volume_alloue_m3', 1000)
            ->assertJsonPath('data.0.quota_eau.volume_consomme_m3', 100)
            ->assertJsonPath('data.0.quota_eau.volume_restant_m3', 900)
            ->assertJsonPath('data.0.statut', 'en_cours');
    }

    public function test_une_campagne_est_affichee_avec_son_quota_restant(): void
    {
        [$user, $campaign] = $this->contexte();

        app(WaterBudgetService::class)->allocateBudget($campaign, 1000, 80);

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/campaigns/{$campaign->getKey()}")
            ->assertOk()
            ->assertJsonPath('data.id', $campaign->getKey())
            ->assertJsonPath('data.quota_eau.volume_restant_m3', 1000)
            ->assertJsonPath('data.quota_eau.seuil_alerte_pct', 80)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'campagne',
                    'statut',
                    'statut_label',
                    'surface_ha',
                    'parcelle' => ['id', 'code'],
                    'culture' => ['id', 'nom'],
                    'quota_eau' => [
                        'volume_alloue_m3',
                        'volume_consomme_m3',
                        'volume_restant_m3',
                        'pourcentage_consomme',
                        'seuil_alerte_pct',
                        'statut',
                    ],
                ],
            ]);
    }

    public function test_une_campagne_sans_budget_affiche_un_quota_null(): void
    {
        [$user, $campaign] = $this->contexte();

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/campaigns/{$campaign->getKey()}")
            ->assertOk()
            ->assertJsonPath('data.quota_eau', null);
    }

    public function test_les_campagnes_sont_isolees_par_exploitation(): void
    {
        [$user, $campaign] = $this->contexte();

        $autreExploitation = Exploitation::factory()->create();
        $autreUser = User::factory()->responsable()->pourExploitation($autreExploitation)->create();
        $autreParcelle = Parcelle::factory()->create(['exploitation_id' => $autreExploitation->getKey()]);
        $autreCulture = Culture::factory()->create(['exploitation_id' => $autreExploitation->getKey()]);
        $autreCampagne = CycleCulture::factory()->create([
            'exploitation_id' => $autreExploitation->getKey(),
            'parcelle_id' => $autreParcelle->getKey(),
            'culture_id' => $autreCulture->getKey(),
            'statut' => StatutCycleCulture::EnCours,
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/campaigns')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/campaigns/{$autreCampagne->getKey()}")
            ->assertNotFound();

        $this->actingAs($autreUser, 'sanctum')
            ->getJson("/api/v1/campaigns/{$campaign->getKey()}")
            ->assertNotFound();
    }

    public function test_une_campagne_inexistante_renvoie_404(): void
    {
        [$user] = $this->contexte();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/campaigns/999999')
            ->assertNotFound();
    }

    public function test_les_irrigations_d_une_campagne_sont_listees(): void
    {
        [$user, $campaign, $parcelle, $source] = $this->contexte();

        app(WaterBudgetService::class)->allocateBudget($campaign, 1000);

        $service = app(IrrigationService::class);

        $service->recordIrrigation($this->donneesIrrigation($campaign, $parcelle, $source), $user);
        $service->recordIrrigation($this->donneesIrrigation($campaign, $parcelle, $source), $user);

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/campaigns/{$campaign->getKey()}/irrigations")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.volume_m3', 60)
            ->assertJsonPath('data.0.campaign_id', $campaign->getKey())
            ->assertJsonStructure([
                'data' => [
                    ['id', 'date', 'duree_minutes', 'debit_m3_h', 'volume_m3', 'parcelle', 'source_eau', 'utilisateur'],
                ],
            ]);
    }

    public function test_une_irrigation_est_enregistree_via_l_api_avec_le_meme_quota(): void
    {
        [$user, $campaign, $parcelle, $source] = $this->contexte();

        app(WaterBudgetService::class)->allocateBudget($campaign, 1000);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/campaigns/{$campaign->getKey()}/irrigations", [
                'production_unit_id' => $parcelle->getKey(),
                'water_source_id' => $source->getKey(),
                'date' => now()->toDateString(),
                'duree_minutes' => 120,
                'debit_m3_h' => 50,
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.volume_m3', 100)
            ->assertJsonPath('data.campaign_id', $campaign->getKey())
            ->assertJsonPath('data.parcelle.code', $parcelle->code);

        $budget = CampaignWaterBudget::withoutGlobalScopes()->sole();

        $this->assertSame(100.0, (float) $budget->volume_consomme_m3);
        $this->assertSame(1, Irrigation::withoutGlobalScopes()->count());
        $this->assertSame(1, Depense::withoutGlobalScopes()->count());
    }

    public function test_une_irrigation_qui_depasse_le_quota_renvoie_409_avec_un_message_clair(): void
    {
        [$user, $campaign, $parcelle, $source] = $this->contexte();

        app(WaterBudgetService::class)->allocateBudget($campaign, 100);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/campaigns/{$campaign->getKey()}/irrigations", [
                'production_unit_id' => $parcelle->getKey(),
                'water_source_id' => $source->getKey(),
                'date' => now()->toDateString(),
                'duree_minutes' => 240,
                'debit_m3_h' => 20,
            ])->assertCreated();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/campaigns/{$campaign->getKey()}/irrigations", [
                'production_unit_id' => $parcelle->getKey(),
                'water_source_id' => $source->getKey(),
                'date' => now()->toDateString(),
                'duree_minutes' => 90,
                'debit_m3_h' => 20,
            ]);

        $response->assertStatus(409)
            ->assertJsonPath('error', 'quota_eau_depasse');

        $this->assertStringContainsString("Quota d'eau dépassé", $response->json('message'));

        $this->assertSame(1, Irrigation::withoutGlobalScopes()->count());
        $this->assertSame(80.0, (float) CampaignWaterBudget::withoutGlobalScopes()->sole()->volume_consomme_m3);
    }

    public function test_une_irrigation_invalide_renvoie_422(): void
    {
        [$user, $campaign, $parcelle, $source] = $this->contexte();

        app(WaterBudgetService::class)->allocateBudget($campaign, 1000);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/campaigns/{$campaign->getKey()}/irrigations", [
                'production_unit_id' => $parcelle->getKey(),
                'water_source_id' => $source->getKey(),
                'date' => now()->toDateString(),
                'duree_minutes' => 0,
                'debit_m3_h' => 0,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['duree_minutes', 'debit_m3_h']);
    }

    public function test_un_utilisateur_sans_exploitation_recoit_403(): void
    {
        [$user, $campaign, $parcelle, $source] = $this->contexte();

        $sansExploitation = User::factory()->agent()->create([
            'exploitation_id' => null,
        ]);

        app(WaterBudgetService::class)->allocateBudget($campaign, 1000);

        $this->actingAs($sansExploitation, 'sanctum')
            ->postJson("/api/v1/campaigns/{$campaign->getKey()}/irrigations", [
                'production_unit_id' => $parcelle->getKey(),
                'water_source_id' => $source->getKey(),
                'date' => now()->toDateString(),
                'duree_minutes' => 60,
                'debit_m3_h' => 20,
            ])->assertForbidden();
    }

    public function test_une_irrigation_sur_une_campagne_d_une_autre_exploitation_renvoie_404(): void
    {
        [$user, $campaign, $parcelle, $source] = $this->contexte();

        $autreExploitation = Exploitation::factory()->create();
        $autreParcelle = Parcelle::factory()->create(['exploitation_id' => $autreExploitation->getKey()]);
        $autreCulture = Culture::factory()->create(['exploitation_id' => $autreExploitation->getKey()]);
        $autreCampagne = CycleCulture::factory()->create([
            'exploitation_id' => $autreExploitation->getKey(),
            'parcelle_id' => $autreParcelle->getKey(),
            'culture_id' => $autreCulture->getKey(),
            'statut' => StatutCycleCulture::EnCours,
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson("/api/v1/campaigns/{$autreCampagne->getKey()}/irrigations", [
                'production_unit_id' => $parcelle->getKey(),
                'water_source_id' => $source->getKey(),
                'date' => now()->toDateString(),
                'duree_minutes' => 60,
                'debit_m3_h' => 20,
            ])->assertNotFound();

        $this->assertSame(0, Irrigation::withoutGlobalScopes()->count());
    }

    /**
     * @return array{0: User, 1: CycleCulture, 2: Parcelle, 3: WaterSource}
     */
    private function contexte(): array
    {
        $exploitation = Exploitation::factory()->create();
        $user = User::factory()->responsable()->pourExploitation($exploitation)->create();

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
