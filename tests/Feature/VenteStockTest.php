<?php

namespace Tests\Feature;

use App\Enums\StatutLotStock;
use App\Enums\StatutVente;
use App\Exceptions\StockInsuffisantException;
use App\Models\Exploitation;
use App\Models\Intrant;
use App\Models\LotStock;
use App\Models\Partenaire;
use App\Models\User;
use App\Services\VenteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VenteStockTest extends TestCase
{
    use RefreshDatabase;

    public function test_valider_une_vente_decremente_le_stock_et_empeche_la_survente(): void
    {
        $exploitation = Exploitation::factory()->create();
        $user = User::factory()->responsable()->pourExploitation($exploitation)->create();
        $intrant = Intrant::factory()->create(['exploitation_id' => $exploitation->getKey()]);
        $partenaire = Partenaire::factory()->client()->create(['exploitation_id' => $exploitation->getKey()]);

        $lot = LotStock::factory()->create([
            'exploitation_id' => $exploitation->getKey(),
            'intrant_id' => $intrant->getKey(),
            'quantite_initiale' => 100,
            'quantite_restante' => 100,
            'statut' => StatutLotStock::Disponible,
        ]);

        $ventes = app(VenteService::class);

        $vente = $ventes->creer([
            'partenaire_id' => $partenaire->getKey(),
            'date_vente' => now()->toDateString(),
            'taux_tva' => 5.5,
            'lignes' => [[
                'lot_stock_id' => $lot->getKey(),
                'designation' => 'Engrais',
                'quantite' => 80,
                'unite' => 'kg',
                'prix_unitaire' => 2,
            ]],
        ], $user);

        $ventes->valider($vente, $user);

        $this->assertSame(20.0, (float) $lot->refresh()->quantite_restante);
        $this->assertSame(StatutVente::Validee, $vente->refresh()->statut);
        $this->assertSame(160.0, (float) $vente->total_ht);
        $this->assertSame(8.8, (float) $vente->tva);
        $this->assertSame(168.8, (float) $vente->total_ttc);

        $this->assertDatabaseHas('mouvements_stock', [
            'lot_stock_id' => $lot->getKey(),
            'vente_id' => $vente->getKey(),
            'type' => 'sortie',
        ]);

        $survente = $ventes->creer([
            'partenaire_id' => $partenaire->getKey(),
            'date_vente' => now()->toDateString(),
            'taux_tva' => 0,
            'lignes' => [[
                'lot_stock_id' => $lot->getKey(),
                'designation' => 'Engrais',
                'quantite' => 30,
                'unite' => 'kg',
                'prix_unitaire' => 2,
            ]],
        ], $user);

        try {
            $ventes->valider($survente, $user);
            $this->fail('La survente aurait dû être refusée.');
        } catch (StockInsuffisantException) {
            $this->assertSame(20.0, (float) $lot->refresh()->quantite_restante);
            $this->assertSame(0, $survente->refresh()->mouvementsStock()->count());
        }
    }

    public function test_annuler_une_vente_validee_reintegre_le_stock(): void
    {
        $exploitation = Exploitation::factory()->create();
        $user = User::factory()->responsable()->pourExploitation($exploitation)->create();
        $intrant = Intrant::factory()->create(['exploitation_id' => $exploitation->getKey()]);
        $partenaire = Partenaire::factory()->client()->create(['exploitation_id' => $exploitation->getKey()]);

        $lot = LotStock::factory()->create([
            'exploitation_id' => $exploitation->getKey(),
            'intrant_id' => $intrant->getKey(),
            'quantite_initiale' => 100,
            'quantite_restante' => 100,
        ]);

        $ventes = app(VenteService::class);
        $vente = $ventes->creer([
            'partenaire_id' => $partenaire->getKey(),
            'date_vente' => now()->toDateString(),
            'taux_tva' => 0,
            'lignes' => [[
                'lot_stock_id' => $lot->getKey(),
                'designation' => 'Engrais',
                'quantite' => 40,
                'unite' => 'kg',
                'prix_unitaire' => 2,
            ]],
        ], $user);

        $ventes->valider($vente, $user);
        $this->assertSame(60.0, (float) $lot->refresh()->quantite_restante);

        $ventes->annuler($vente, $user);

        $this->assertSame(100.0, (float) $lot->refresh()->quantite_restante);
        $this->assertSame(StatutVente::Annulee, $vente->refresh()->statut);
    }
}
