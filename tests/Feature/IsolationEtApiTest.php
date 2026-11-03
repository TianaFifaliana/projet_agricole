<?php

namespace Tests\Feature;

use App\Models\Exploitation;
use App\Models\Intrant;
use App\Models\LotStock;
use App\Models\Parcelle;
use App\Models\Partenaire;
use App\Models\User;
use App\Models\Vente;
use App\Services\VenteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IsolationEtApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_les_donnees_sont_isolees_par_exploitation(): void
    {
        $exploitationA = Exploitation::factory()->create();
        $exploitationB = Exploitation::factory()->create();

        $userA = User::factory()->responsable()->pourExploitation($exploitationA)->create();
        $parcelleA = Parcelle::factory()->create([
            'exploitation_id' => $exploitationA->getKey(),
            'code' => 'A-01',
        ]);
        $parcelleB = Parcelle::factory()->create([
            'exploitation_id' => $exploitationB->getKey(),
            'code' => 'B-01',
        ]);

        $this->actingAs($userA)
            ->get(route('parcelles.index'))
            ->assertOk()
            ->assertSee($parcelleA->code)
            ->assertDontSee($parcelleB->code);
    }

    public function test_l_api_exige_un_token_et_renvoie_le_token_de_connexion(): void
    {
        $exploitation = Exploitation::factory()->create();
        $user = User::factory()->responsable()->pourExploitation($exploitation)->create([
            'email' => 'api@agrisaas.test',
        ]);
        $parcelle = Parcelle::factory()->create([
            'exploitation_id' => $exploitation->getKey(),
        ]);

        $this->getJson('/api/v1/parcelles')->assertUnauthorized();

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => 'api@agrisaas.test',
            'password' => 'password',
            'device_name' => 'phpunit',
        ])->assertOk()->assertJsonStructure(['token', 'user' => ['id', 'role']]);

        $token = $login->json('token');

        $this->withToken($token)
            ->getJson('/api/v1/parcelles')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $parcelle->getKey());

        $this->withToken($token)
            ->postJson('/api/v1/auth/logout')
            ->assertOk();
    }

    public function test_un_agent_ne_peut_pas_valider_une_vente_via_l_api(): void
    {
        $exploitation = Exploitation::factory()->create();
        $agent = User::factory()->agent()->pourExploitation($exploitation)->create();
        $responsable = User::factory()->responsable()->pourExploitation($exploitation)->create();

        $intrant = Intrant::factory()->create(['exploitation_id' => $exploitation->getKey()]);
        $partenaire = Partenaire::factory()->client()->create(['exploitation_id' => $exploitation->getKey()]);
        $lot = LotStock::factory()->create([
            'exploitation_id' => $exploitation->getKey(),
            'intrant_id' => $intrant->getKey(),
            'quantite_initiale' => 100,
            'quantite_restante' => 100,
        ]);

        $vente = app(VenteService::class)->creer([
            'partenaire_id' => $partenaire->getKey(),
            'date_vente' => now()->toDateString(),
            'taux_tva' => 0,
            'lignes' => [[
                'lot_stock_id' => $lot->getKey(),
                'designation' => 'Engrais',
                'quantite' => 10,
                'unite' => 'kg',
                'prix_unitaire' => 2,
            ]],
        ], $responsable);

        $this->assertInstanceOf(Vente::class, $vente);

        $this->actingAs($agent, 'sanctum')
            ->postJson("/api/v1/ventes/{$vente->getKey()}/valider")
            ->assertForbidden();
    }
}
