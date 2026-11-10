<?php

namespace Tests\Feature;

use App\Enums\StatutCycleCulture;
use App\Models\CampaignWaterBudget;
use App\Models\Culture;
use App\Models\CycleCulture;
use App\Models\Depense;
use App\Models\Exploitation;
use App\Models\Intrant;
use App\Models\JournalAudit;
use App\Models\LotStock;
use App\Models\Parcelle;
use App\Models\Partenaire;
use App\Models\User;
use App\Models\Vente;
use App\Services\DashboardService;
use App\Services\FinanceService;
use App\Support\ExploitationCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DashboardRizicultureTest extends TestCase
{
    use RefreshDatabase;

    public function test_les_indicateurs_riziculture_sont_calcules_par_campagne(): void
    {
        [$user, $parcelle, $culture] = $this->contexte();
        $exploitationId = $user->exploitation_id;

        $cycle = CycleCulture::factory()->recolte()->create([
            'exploitation_id' => $exploitationId,
            'parcelle_id' => $parcelle->getKey(),
            'culture_id' => $culture->getKey(),
            'surface_ha' => 10,
            'quantite_recoltee_kg' => 5000,
        ]);

        CampaignWaterBudget::factory()->create([
            'campaign_id' => $cycle->getKey(),
            'volume_alloue_m3' => 10000,
            'volume_consomme_m3' => 2500,
        ]);

        $indicateurs = app(DashboardService::class)->indicateurs($exploitationId);
        $campagne = $indicateurs['campagnes'][0];

        $this->assertSame(250.0, $campagne['consommation_eau_m3_ha']);
        $this->assertSame(0.5, $campagne['rendement_tha']);
        $this->assertSame(2.0, $campagne['productivite_eau_kg_m3']);

        $this->assertSame(250.0, $indicateurs['consommation_eau_m3_ha']);
        $this->assertSame(0.5, $indicateurs['rendement_tha']);
        $this->assertSame(2.0, $indicateurs['productivite_eau_kg_m3']);
    }

    public function test_les_indicateurs_sont_absents_sans_recolte_ni_budget(): void
    {
        [$user, $parcelle, $culture] = $this->contexte();
        $exploitationId = $user->exploitation_id;

        $this->campagne($exploitationId, $parcelle, $culture, ['quantite_recoltee_kg' => null]);

        $indicateurs = app(DashboardService::class)->indicateurs($exploitationId);

        $this->assertNull($indicateurs['campagnes'][0]['consommation_eau_m3_ha']);
        $this->assertNull($indicateurs['campagnes'][0]['rendement_tha']);
        $this->assertNull($indicateurs['campagnes'][0]['productivite_eau_kg_m3']);
        $this->assertNull($indicateurs['productivite_eau_kg_m3']);
    }

    public function test_les_campagnes_actives_et_la_superficie_exploitee_sont_comptees(): void
    {
        [$user, $parcelle, $culture] = $this->contexte();
        $exploitationId = $user->exploitation_id;

        $this->campagne($exploitationId, $parcelle, $culture);

        $statistiques = app(DashboardService::class)->statistiques($exploitationId);

        $this->assertSame(1, $statistiques['campagnes_actives']['total']);
        $this->assertSame(10.0, $statistiques['campagnes_actives']['superficie_ha']);

        $this->campagne($exploitationId, $parcelle, $culture, ['surface_ha' => 5]);

        ExploitationCache::invalidate($exploitationId);

        $statistiques = app(DashboardService::class)->statistiques($exploitationId);

        $this->assertSame(2, $statistiques['campagnes_actives']['total']);
        $this->assertSame(15.0, $statistiques['campagnes_actives']['superficie_ha']);
    }

    public function test_l_etat_des_stocks_est_liste_par_categorie_avec_les_critiques(): void
    {
        [$user] = $this->contexte();
        $exploitationId = $user->exploitation_id;

        $semences = Intrant::factory()->create([
            'exploitation_id' => $exploitationId,
            'nom' => 'Semences de riz',
            'categorie' => 'semence',
            'unite' => 'kg',
            'seuil_alerte' => 100,
        ]);

        $uree = Intrant::factory()->create([
            'exploitation_id' => $exploitationId,
            'nom' => 'Urée',
            'categorie' => 'engrais',
            'unite' => 'kg',
            'seuil_alerte' => 500,
        ]);

        LotStock::factory()->create([
            'exploitation_id' => $exploitationId,
            'intrant_id' => $semences->getKey(),
            'quantite_initiale' => 1000,
            'quantite_restante' => 800,
        ]);

        LotStock::factory()->create([
            'exploitation_id' => $exploitationId,
            'intrant_id' => $uree->getKey(),
            'quantite_initiale' => 600,
            'quantite_restante' => 300,
        ]);

        $statistiques = app(DashboardService::class)->statistiques($exploitationId);

        $this->assertCount(2, $statistiques['stocks']);

        $stockSemences = collect($statistiques['stocks'])->firstWhere('nom', 'Semences de riz');
        $this->assertSame('semence', $stockSemences['categorie']);
        $this->assertSame(800.0, $stockSemences['stock_disponible']);
        $this->assertFalse($stockSemences['critique']);

        $stockUree = collect($statistiques['stocks'])->firstWhere('nom', 'Urée');
        $this->assertSame('engrais', $stockUree['categorie']);
        $this->assertSame(300.0, $stockUree['stock_disponible']);
        $this->assertTrue($stockUree['critique']);

        $this->assertCount(1, $statistiques['alertes_stock']);
        $this->assertSame('Urée', $statistiques['alertes_stock'][0]['nom']);
    }

    public function test_les_alertes_de_quota_d_eau_proviennent_des_seuils(): void
    {
        [$user, $parcelle, $culture] = $this->contexte();
        $exploitationId = $user->exploitation_id;
        $campagne = $this->campagne($exploitationId, $parcelle, $culture);

        CampaignWaterBudget::factory()->create([
            'exploitation_id' => $exploitationId,
            'campaign_id' => $campagne->getKey(),
            'volume_alloue_m3' => 1000,
            'volume_consomme_m3' => 850,
            'seuil_alerte_pct' => 80,
        ]);

        $eau = app(DashboardService::class)->eau($exploitationId);

        $this->assertCount(1, $eau['alertes_quotas']);
        $this->assertSame(85.0, $eau['alertes_quotas'][0]['pourcentage_consomme']);
        $this->assertSame(80.0, $eau['alertes_quotas'][0]['seuil_alerte_pct']);
    }

    public function test_l_evolution_mensuelle_agregee_recettes_et_depenses_par_mois(): void
    {
        [$user] = $this->contexte();
        $exploitationId = $user->exploitation_id;
        $partenaire = Partenaire::factory()->create(['exploitation_id' => $exploitationId]);

        Vente::factory()->validee()->create([
            'exploitation_id' => $exploitationId,
            'partenaire_id' => $partenaire->getKey(),
            'user_id' => $user->getKey(),
            'total_ht' => 100,
            'tva' => 0,
            'total_ttc' => 100,
            'date_vente' => now()->startOfYear()->toDateString(),
        ]);

        Depense::factory()->create([
            'exploitation_id' => $exploitationId,
            'montant' => 40,
            'date' => now()->startOfYear()->addMonths(2)->toDateString(),
        ]);

        $evolution = app(FinanceService::class)->evolutionMensuelle($exploitationId);

        $this->assertSame(12, count($evolution['recettes']));
        $this->assertSame(12, count($evolution['depenses']));
        $this->assertSame(100.0, $evolution['recettes'][0]);
        $this->assertSame(40.0, $evolution['depenses'][2]);
        $this->assertSame(0.0, $evolution['recettes'][1]);
    }

    public function test_les_activites_recentes_proviennent_du_journal(): void
    {
        [$user] = $this->contexte();
        $exploitationId = $user->exploitation_id;

        JournalAudit::factory()->count(3)->create([
            'exploitation_id' => $exploitationId,
            'user_id' => $user->getKey(),
        ]);

        $activites = app(DashboardService::class)->activites($exploitationId);

        $this->assertCount(3, $activites);
        $this->assertSame($user->nomComplet(), $activites[0]['utilisateur']);
        $this->assertArrayHasKey('action', $activites[0]);
        $this->assertArrayHasKey('date', $activites[0]);
    }

    public function test_les_nouvelles_donnees_sont_mises_en_cache_puis_invalidees_apres_une_ecriture(): void
    {
        [$user] = $this->contexte();
        $exploitationId = $user->exploitation_id;

        $dashboard = app(DashboardService::class);
        $finance = app(FinanceService::class);

        $dashboard->indicateurs($exploitationId);
        $dashboard->activites($exploitationId);
        $finance->evolutionMensuelle($exploitationId);

        $this->assertTrue(Cache::has(ExploitationCache::indicatorsKey($exploitationId)));
        $this->assertTrue(Cache::has(ExploitationCache::activitiesKey($exploitationId)));
        $this->assertTrue(Cache::has(ExploitationCache::financeEvolutionKey($exploitationId)));

        Depense::factory()->create(['exploitation_id' => $exploitationId]);

        $this->assertFalse(Cache::has(ExploitationCache::indicatorsKey($exploitationId)));
        $this->assertFalse(Cache::has(ExploitationCache::activitiesKey($exploitationId)));
        $this->assertFalse(Cache::has(ExploitationCache::financeEvolutionKey($exploitationId)));
    }

    public function test_la_page_du_tableau_de_bord_affiche_les_blocs_riziculture(): void
    {
        [$user] = $this->contexte();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Indicateurs riziculture', false)
            ->assertSee('Consommation d\'eau par hectare', false)
            ->assertSee('Productivité de l\'eau', false)
            ->assertSee('chart-quota-eau', false)
            ->assertSee('chart-finances', false)
            ->assertSee('chart-rendement', false)
            ->assertSee('État des stocks', false)
            ->assertSee('Marge simplifiée', false)
            ->assertSee('Activités récentes', false);
    }

    /**
     * @return array{0: User, 1: Parcelle, 2: Culture}
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

        return [$user, $parcelle, $culture];
    }

    private function campagne(int $exploitationId, Parcelle $parcelle, Culture $culture, array $attributes = []): CycleCulture
    {
        return CycleCulture::factory()->create(array_merge([
            'exploitation_id' => $exploitationId,
            'parcelle_id' => $parcelle->getKey(),
            'culture_id' => $culture->getKey(),
            'surface_ha' => 10,
            'statut' => StatutCycleCulture::EnCours,
        ], $attributes));
    }
}
