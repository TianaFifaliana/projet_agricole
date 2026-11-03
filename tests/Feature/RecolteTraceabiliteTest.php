<?php

namespace Tests\Feature;

use App\Enums\StatutCycleCulture;
use App\Enums\TypeMouvementStock;
use App\Models\Culture;
use App\Models\Exploitation;
use App\Models\Intervention;
use App\Models\LotStock;
use App\Models\Parcelle;
use App\Models\User;
use App\Services\CycleCultureService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class RecolteTraceabiliteTest extends TestCase
{
    use DatabaseMigrations;

    public function test_recolte_cree_un_lot_tracable_et_un_mouvement_de_stock(): void
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

        $cycles = app(CycleCultureService::class);

        $cycle = $cycles->creer([
            'parcelle_id' => $parcelle->getKey(),
            'culture_id' => $culture->getKey(),
            'campagne' => '2026',
            'date_semis' => '2026-04-01',
            'date_recolte_prevue' => '2026-09-01',
            'surface_ha' => 8,
        ]);

        $intervention = $cycles->enregistrerRecolte($cycle, [
            'quantite_kg' => 5000,
            'date_recolte' => now()->toDateString(),
        ], $user);

        $cycle->refresh();

        $this->assertSame(StatutCycleCulture::Recolte, $cycle->statut);
        $this->assertSame(5000.0, (float) $cycle->quantite_recoltee_kg);
        $this->assertSame(0.63, (float) $cycle->rendement_obtenu_tha);

        $lot = LotStock::withoutGlobalScopes()
            ->where('cycle_culture_id', $cycle->getKey())
            ->first();

        $this->assertNotNull($lot);
        $this->assertSame(5000.0, (float) $lot->quantite_restante);
        $this->assertSame($parcelle->getKey(), $lot->parcelle_id);
        $this->assertSame($intervention->getKey(), $lot->intervention_id);

        $this->assertDatabaseHas('mouvements_stock', [
            'lot_stock_id' => $lot->getKey(),
            'type' => TypeMouvementStock::Entree->value,
        ]);

        $this->assertDatabaseHas('journal_audits', [
            'action' => 'recolte',
            'entite' => Intervention::class,
            'entite_id' => $intervention->getKey(),
        ]);
    }
}
