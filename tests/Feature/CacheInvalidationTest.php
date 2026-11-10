<?php

namespace Tests\Feature;

use App\Enums\StatutCycleCulture;
use App\Models\Culture;
use App\Models\CycleCulture;
use App\Models\Depense;
use App\Models\Exploitation;
use App\Models\Intrant;
use App\Models\Irrigation;
use App\Models\LotStock;
use App\Models\MouvementStock;
use App\Models\Parcelle;
use App\Models\Partenaire;
use App\Models\User;
use App\Models\Vente;
use App\Models\WaterSource;
use App\Services\DashboardService;
use App\Services\FinanceService;
use App\Services\IrrigationService;
use App\Services\WaterBudgetService;
use App\Support\ExploitationCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CacheInvalidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_le_cache_est_vide_apres_une_irrigation(): void
    {
        [$user, $campaign, $parcelle, $source] = $this->contexte();
        $exploitationId = $user->exploitation_id;

        $budget = app(WaterBudgetService::class)->allocateBudget($campaign, 1000, 80);

        $dashboard = app(DashboardService::class);
        $finance = app(FinanceService::class);

        $dashboard->statistiques($exploitationId);
        $dashboard->eau($exploitationId);
        $finance->marge($exploitationId);

        foreach (ExploitationCache::keys($exploitationId) as $key) {
            $this->assertTrue(Cache::has($key));
        }

        app(IrrigationService::class)->recordIrrigation([
            'campaign_id' => $campaign->getKey(),
            'production_unit_id' => $parcelle->getKey(),
            'water_source_id' => $source->getKey(),
            'date' => now()->toDateString(),
            'duree_minutes' => 120,
            'debit_m3_h' => 100,
        ], $user);

        foreach (ExploitationCache::keys($exploitationId) as $key) {
            $this->assertFalse(Cache::has($key));
        }

        $eau = $dashboard->eau($exploitationId);

        $this->assertSame(200.0, $eau['total_consomme_m3']);
        $this->assertSame(1, count($eau['campagnes']));
        $this->assertSame($budget->getKey(), $eau['campagnes'][0]['budget_id']);
    }

    public function test_les_statistiques_sont_mises_en_cache_puis_recalculees_apres_invalidation(): void
    {
        [$user] = $this->contexte();
        $exploitationId = $user->exploitation_id;

        $dashboard = app(DashboardService::class);

        $this->assertSame(1, $dashboard->statistiques($exploitationId)['parcelles']['total']);
        $this->assertTrue(Cache::has(ExploitationCache::statsKey($exploitationId)));

        Parcelle::factory()->create([
            'exploitation_id' => $exploitationId,
            'actif' => true,
        ]);

        $this->assertSame(1, $dashboard->statistiques($exploitationId)['parcelles']['total']);

        ExploitationCache::invalidate($exploitationId);

        $this->assertSame(2, $dashboard->statistiques($exploitationId)['parcelles']['total']);
    }

    public function test_les_volumes_d_eau_sont_mis_en_cache_par_campagne_active(): void
    {
        [$user, $campaign] = $this->contexte();
        $exploitationId = $user->exploitation_id;

        $budget = app(WaterBudgetService::class)->allocateBudget($campaign, 1000, 80);

        $dashboard = app(DashboardService::class);

        $this->assertSame(0.0, $dashboard->eau($exploitationId)['total_consomme_m3']);
        $this->assertTrue(Cache::has(ExploitationCache::waterKey($exploitationId)));

        DB::table('campaign_water_budgets')
            ->where('id', $budget->getKey())
            ->update(['volume_consomme_m3' => 250]);

        $this->assertSame(0.0, $dashboard->eau($exploitationId)['total_consomme_m3']);

        ExploitationCache::invalidate($exploitationId);

        $this->assertSame(250.0, $dashboard->eau($exploitationId)['total_consomme_m3']);
    }

    public function test_la_marge_est_mise_en_cache_et_invalidee_apres_une_depense(): void
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
            'date_vente' => now()->toDateString(),
        ]);

        $finance = app(FinanceService::class);

        $this->assertSame(100.0, $finance->marge($exploitationId)['marge']);
        $this->assertTrue(Cache::has(ExploitationCache::financeMarginKey($exploitationId)));

        Depense::factory()->create([
            'exploitation_id' => $exploitationId,
            'montant' => 40,
            'date' => now()->toDateString(),
        ]);

        $this->assertFalse(Cache::has(ExploitationCache::financeMarginKey($exploitationId)));

        $marge = $finance->marge($exploitationId);

        $this->assertSame(100.0, $marge['recettes_ht']);
        $this->assertSame(40.0, $marge['depenses']);
        $this->assertSame(60.0, $marge['marge']);
    }

    public function test_chaque_ecriture_metier_invalide_les_cles_de_l_exploitation(): void
    {
        [$user, $campaign, $parcelle, $source] = $this->contexte();
        $exploitationId = $user->exploitation_id;

        $budget = app(WaterBudgetService::class)->allocateBudget($campaign, 1000, 80);
        $culture = Culture::factory()->create(['exploitation_id' => $exploitationId]);
        $intrant = Intrant::factory()->create(['exploitation_id' => $exploitationId]);
        $lot = LotStock::factory()->create([
            'exploitation_id' => $exploitationId,
            'intrant_id' => $intrant->getKey(),
        ]);
        $partenaire = Partenaire::factory()->create(['exploitation_id' => $exploitationId]);

        $ecritures = [
            'irrigation' => fn () => Irrigation::factory()->create([
                'campaign_id' => $campaign->getKey(),
                'production_unit_id' => $parcelle->getKey(),
                'water_source_id' => $source->getKey(),
                'user_id' => $user->getKey(),
            ]),
            'mouvement de stock' => fn () => MouvementStock::factory()->create([
                'lot_stock_id' => $lot->getKey(),
            ]),
            'dépense' => fn () => Depense::factory()->create([
                'exploitation_id' => $exploitationId,
            ]),
            'vente' => fn () => Vente::factory()->create([
                'exploitation_id' => $exploitationId,
                'partenaire_id' => $partenaire->getKey(),
                'user_id' => $user->getKey(),
            ]),
            'récolte' => fn () => CycleCulture::factory()->recolte()->create([
                'exploitation_id' => $exploitationId,
                'parcelle_id' => $parcelle->getKey(),
                'culture_id' => $culture->getKey(),
            ]),
            'budget d\'eau' => fn () => $budget->touch(),
        ];

        foreach ($ecritures as $libelle => $ecriture) {
            $this->remplirCache($exploitationId);

            $ecriture();

            foreach (ExploitationCache::keys($exploitationId) as $key) {
                $this->assertFalse(
                    Cache::has($key),
                    "La clé {$key} n'a pas été invalidée après une écriture de type {$libelle}.",
                );
            }
        }
    }

    public function test_l_invalidation_ne_touche_pas_les_autres_exploitations(): void
    {
        $exploitationA = Exploitation::factory()->create();
        $exploitationB = Exploitation::factory()->create();

        $this->remplirCache($exploitationA->getKey());
        $this->remplirCache($exploitationB->getKey());

        ExploitationCache::invalidate($exploitationA->getKey());

        foreach (ExploitationCache::keys($exploitationA->getKey()) as $key) {
            $this->assertFalse(Cache::has($key));
        }

        foreach (ExploitationCache::keys($exploitationB->getKey()) as $key) {
            $this->assertTrue(Cache::has($key));
        }
    }

    private function remplirCache(int $exploitationId): void
    {
        foreach (ExploitationCache::keys($exploitationId) as $key) {
            Cache::put($key, ['valeurs' => true], 600);
        }
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
}
