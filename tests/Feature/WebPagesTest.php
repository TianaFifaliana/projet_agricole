<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\Culture;
use App\Models\Exploitation;
use App\Models\Intrant;
use App\Models\LotStock;
use App\Models\Parcelle;
use App\Models\Partenaire;
use App\Models\User;
use App\Services\CycleCultureService;
use App\Services\VenteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_farm_user_can_access_main_pages(): void
    {
        [$user, $cycle, $vente] = $this->contexte();

        $this->actingAs($user)->get(route('dashboard'))->assertOk();
        $this->actingAs($user)->get(route('parcelles.index'))->assertOk();
        $this->actingAs($user)->get(route('parcelles.create'))->assertOk();
        $this->actingAs($user)->get(route('cycles.index'))->assertOk();
        $this->actingAs($user)->get(route('cycles.create'))->assertOk();
        $this->actingAs($user)->get(route('cycles.show', $cycle))->assertOk();
        $this->actingAs($user)->get(route('interventions.index'))->assertOk();
        $this->actingAs($user)->get(route('stocks.index'))->assertOk();
        $this->actingAs($user)->get(route('ventes.index'))->assertOk();
        $this->actingAs($user)->get(route('ventes.create'))->assertOk();
        $this->actingAs($user)->get(route('ventes.show', $vente))->assertOk();
        $this->actingAs($user)->get(route('animaux.index'))->assertOk();
        $this->actingAs($user)->get(route('alertes.index'))->assertOk();
    }

    public function test_agent_cannot_create_parcelle_or_cycle_but_can_create_vente(): void
    {
        [$user] = $this->contexte();

        $agent = User::factory()->agent()->pourExploitation($user->exploitation)->create();

        $this->actingAs($agent)->get(route('parcelles.create'))->assertForbidden();
        $this->actingAs($agent)->get(route('cycles.create'))->assertForbidden();
        $this->actingAs($agent)->get(route('ventes.create'))->assertOk();
    }

    public function test_administrateur_can_access_global_pages(): void
    {
        $admin = User::factory()->administrateur()->create();
        Exploitation::factory()->create();

        $this->actingAs($admin)->get(route('dashboard'))->assertOk();
        $this->actingAs($admin)->get(route('parcelles.create'))->assertOk();
        $this->actingAs($admin)->get(route('alertes.index'))->assertOk();
    }

    /**
     * @return array{0: User, 1: \App\Models\CycleCulture, 2: \App\Models\Vente}
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

        $intrant = Intrant::factory()->create([
            'exploitation_id' => $exploitation->getKey(),
            'seuil_alerte' => 10,
        ]);

        $lot = LotStock::factory()->create([
            'exploitation_id' => $exploitation->getKey(),
            'intrant_id' => $intrant->getKey(),
            'quantite_initiale' => 100,
            'quantite_restante' => 100,
        ]);

        $partenaire = Partenaire::factory()->client()->create([
            'exploitation_id' => $exploitation->getKey(),
        ]);

        Animal::factory()->create([
            'exploitation_id' => $exploitation->getKey(),
        ]);

        $cycle = app(CycleCultureService::class)->creer([
            'parcelle_id' => $parcelle->getKey(),
            'culture_id' => $culture->getKey(),
            'campagne' => '2026',
            'date_semis' => '2026-04-01',
            'date_recolte_prevue' => '2026-09-01',
            'surface_ha' => 8,
        ]);

        $vente = app(VenteService::class)->creer([
            'partenaire_id' => $partenaire->getKey(),
            'date_vente' => now()->toDateString(),
            'taux_tva' => 0,
            'lignes' => [
                [
                    'lot_stock_id' => $lot->getKey(),
                    'designation' => 'Lot de test',
                    'quantite' => 10,
                    'unite' => 'kg',
                    'prix_unitaire' => 2.5,
                ],
            ],
        ], $user);

        return [$user, $cycle, $vente];
    }
}
