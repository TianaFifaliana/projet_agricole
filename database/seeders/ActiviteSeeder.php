<?php

namespace Database\Seeders;

use App\Enums\TypeIntervention;
use App\Models\Culture;
use App\Models\Exploitation;
use App\Models\Intrant;
use App\Models\Intervention;
use App\Models\LotStock;
use App\Models\Parcelle;
use App\Models\Partenaire;
use App\Models\User;
use App\Services\CycleCultureService;
use App\Services\StockService;
use App\Services\VenteService;
use Illuminate\Database\Seeder;

class ActiviteSeeder extends Seeder
{
    public function run(CycleCultureService $cycles, StockService $stock, VenteService $ventes): void
    {
        $tilleuls = Exploitation::where('code', 'EXP-001')->firstOrFail();
        $val = Exploitation::where('code', 'EXP-002')->firstOrFail();

        $responsableTilleuls = User::where('email', 'responsable@agrisaas.test')->firstOrFail();
        $agentTilleuls = User::where('email', 'agent@agrisaas.test')->firstOrFail();
        $responsableVal = User::where('email', 'responsable.val@agrisaas.test')->firstOrFail();

        $ble = $this->culture($tilleuls, 'Blé tendre');
        $mais = $this->culture($tilleuls, 'Maïs grain');
        $orge = $this->culture($tilleuls, 'Orge');
        $prairie = $this->culture($tilleuls, 'Prairie permanente');
        $colza = $this->culture($val, 'Colza');

        $p1 = $this->parcelle($tilleuls, 'P-01');
        $p2 = $this->parcelle($tilleuls, 'P-02');
        $p3 = $this->parcelle($tilleuls, 'P-03');
        $p4 = $this->parcelle($tilleuls, 'P-04');
        $v1 = $this->parcelle($val, 'P-01');
        $v2 = $this->parcelle($val, 'P-02');

        $cycleBle = $cycles->creer([
            'parcelle_id' => $p1->getKey(),
            'culture_id' => $ble->getKey(),
            'campagne' => '2025/2026',
            'date_semis' => '2025-10-15',
            'date_recolte_prevue' => '2026-07-10',
            'surface_ha' => 12.00,
            'notes' => 'Blé d\'hiver sur la parcelle historique.',
        ]);

        $cycleMais = $cycles->creer([
            'parcelle_id' => $p2->getKey(),
            'culture_id' => $mais->getKey(),
            'campagne' => '2026',
            'date_semis' => '2026-10-01',
            'date_recolte_prevue' => '2027-03-15',
            'surface_ha' => 7.50,
        ]);

        $cycleOrge = $cycles->creer([
            'parcelle_id' => $p3->getKey(),
            'culture_id' => $orge->getKey(),
            'campagne' => '2025/2026',
            'date_semis' => '2025-11-05',
            'date_recolte_prevue' => '2026-06-25',
            'surface_ha' => 6.00,
        ]);

        $cycles->creer([
            'parcelle_id' => $p4->getKey(),
            'culture_id' => $prairie->getKey(),
            'campagne' => '2024',
            'date_semis' => '2024-03-01',
            'date_recolte_prevue' => '2025-03-01',
            'surface_ha' => 4.00,
        ]);

        $cycleColza = $cycles->creer([
            'parcelle_id' => $v1->getKey(),
            'culture_id' => $colza->getKey(),
            'campagne' => '2025/2026',
            'date_semis' => '2025-09-01',
            'date_recolte_prevue' => '2026-07-01',
            'surface_ha' => 9.00,
        ]);

        $cycles->creer([
            'parcelle_id' => $v2->getKey(),
            'culture_id' => $this->culture($val, 'Maïs grain')->getKey(),
            'campagne' => '2026',
            'date_semis' => '2026-04-15',
            'date_recolte_prevue' => '2026-09-30',
            'surface_ha' => 7.00,
        ]);

        $this->intervention($tilleuls, $p1, $cycleBle, $agentTilleuls, TypeIntervention::Fertilisation, '2026-03-20', 350, 'kg', 180.00, 'Azote de printemps');
        $this->intervention($tilleuls, $p1, $cycleBle, $agentTilleuls, TypeIntervention::Traitement, '2026-05-12', 12, 'L', 240.00, 'Fongicide foliaire');
        $this->intervention($tilleuls, $p2, $cycleMais, $agentTilleuls, TypeIntervention::Semis, '2026-10-01', 45, 'kg', 95.00, 'Semis maïs');
        $this->intervention($tilleuls, $p3, $cycleOrge, $agentTilleuls, TypeIntervention::Irrigation, '2026-05-05', null, null, 120.00, 'Irrigation d\'appoint');

        $this->intervention($val, $v1, $cycleColza, $responsableVal, TypeIntervention::Fertilisation, '2026-03-08', 280, 'kg', 150.00, 'Fertilisation colza');

        $cycles->enregistrerRecolte($cycleBle, [
            'quantite_kg' => 48000,
            'date_recolte' => '2026-07-20',
            'description' => 'Récolte de blé, conditions sèches.',
        ], $agentTilleuls);

        $cycles->enregistrerRecolte($cycleOrge, [
            'quantite_kg' => 24000,
            'date_recolte' => '2026-06-30',
            'description' => 'Récolte d\'orge.',
        ], $agentTilleuls);

        $cycles->enregistrerRecolte($cycleColza, [
            'quantite_kg' => 27000,
            'date_recolte' => '2026-07-05',
            'description' => 'Récolte de colza.',
        ], $responsableVal);

        $this->creerStock($tilleuls, $stock, $agentTilleuls);
        $this->creerStock($val, $stock, $responsableVal);

        $this->creerVentes($tilleuls, $ventes, $responsableTilleuls, $cycleBle, $cycleOrge);
        $this->creerVentes($val, $ventes, $responsableVal, $cycleColza);
    }

    private function culture(Exploitation $exploitation, string $nom): Culture
    {
        return Culture::withoutGlobalScopes()
            ->where('exploitation_id', $exploitation->getKey())
            ->where('nom', $nom)
            ->firstOrFail();
    }

    private function parcelle(Exploitation $exploitation, string $code): Parcelle
    {
        return Parcelle::withoutGlobalScopes()
            ->where('exploitation_id', $exploitation->getKey())
            ->where('code', $code)
            ->firstOrFail();
    }

    private function intrant(Exploitation $exploitation, string $nom): Intrant
    {
        return Intrant::withoutGlobalScopes()
            ->where('exploitation_id', $exploitation->getKey())
            ->where('nom', $nom)
            ->firstOrFail();
    }

    private function intervention(
        Exploitation $exploitation,
        Parcelle $parcelle,
        \App\Models\CycleCulture $cycle,
        User $operateur,
        TypeIntervention $type,
        string $date,
        ?float $quantite,
        ?string $unite,
        ?float $cout,
        ?string $description,
    ): Intervention {
        return Intervention::create([
            'exploitation_id' => $exploitation->getKey(),
            'parcelle_id' => $parcelle->getKey(),
            'cycle_culture_id' => $cycle->getKey(),
            'operateur_id' => $operateur->getKey(),
            'type' => $type,
            'date_intervention' => $date,
            'quantite' => $quantite,
            'unite' => $unite,
            'cout' => $cout,
            'description' => $description,
        ]);
    }

    private function creerStock(Exploitation $exploitation, StockService $stock, User $user): void
    {
        $engrais = $this->intrant($exploitation, 'Engrais NPK 15-15-15');
        $semence = $this->intrant($exploitation, 'Semence blé Apache');
        $fongicide = $this->intrant($exploitation, 'Fongicide foliaire');
        $aliment = $this->intrant($exploitation, 'Aliment bovin');
        $gasoil = $this->intrant($exploitation, 'Gasoil non routier');

        $entrees = [
            ['code' => 'ACH-ENG-'.$exploitation->getKey(), 'intrant' => $engrais, 'quantite' => 800, 'date' => '2026-03-01'],
            ['code' => 'ACH-SEM-'.$exploitation->getKey(), 'intrant' => $semence, 'quantite' => 250, 'date' => '2025-09-20'],
            ['code' => 'ACH-FON-'.$exploitation->getKey(), 'intrant' => $fongicide, 'quantite' => 80, 'date' => '2026-04-01'],
            ['code' => 'ACH-ALI-'.$exploitation->getKey(), 'intrant' => $aliment, 'quantite' => 500, 'date' => '2026-10-01'],
            ['code' => 'ACH-GAS-'.$exploitation->getKey(), 'intrant' => $gasoil, 'quantite' => 50, 'date' => '2026-09-15'],
        ];

        foreach ($entrees as $entree) {
            $lot = $stock->entrer(
                exploitationId: $exploitation->getKey(),
                codeLot: $entree['code'],
                quantite: $entree['quantite'],
                unite: $entree['intrant']->unite,
                attributes: [
                    'intrant_id' => $entree['intrant']->getKey(),
                    'date_entree' => $entree['date'],
                    'origine' => 'achat',
                    'prix_unitaire' => $entree['intrant']->prix_unitaire,
                    'motif' => 'Entrée initiale de stock',
                ],
                user: $user,
            );

            if ($entree['intrant']->nom === 'Semence blé Apache') {
                $stock->sortir(
                    lot: $lot,
                    quantite: 100,
                    motif: 'Consommation pour semis',
                    user: $user,
                );
            }
        }
    }

    private function creerVentes(
        Exploitation $exploitation,
        VenteService $ventes,
        User $user,
        \App\Models\CycleCulture ...$cycles,
    ): void {
        $client = Partenaire::withoutGlobalScopes()
            ->where('exploitation_id', $exploitation->getKey())
            ->where('nom', 'Coopérative Agricole du Sud')
            ->firstOrFail();

        foreach ($cycles as $index => $cycle) {
            $lot = LotStock::withoutGlobalScopes()
                ->where('exploitation_id', $exploitation->getKey())
                ->where('cycle_culture_id', $cycle->getKey())
                ->firstOrFail();

            $vente = $ventes->creer([
                'partenaire_id' => $client->getKey(),
                'date_vente' => now()->toDateString(),
                'taux_tva' => 0,
                'notes' => 'Vente de démonstration.',
                'lignes' => [
                    [
                        'lot_stock_id' => $lot->getKey(),
                        'designation' => $cycle->culture->nom,
                        'quantite' => $index === 0 ? 12000 : 8000,
                        'unite' => 'kg',
                        'prix_unitaire' => 0.25,
                    ],
                ],
            ], $user);

            $ventes->valider($vente, $user);
        }

        $lotBrouillon = LotStock::withoutGlobalScopes()
            ->where('exploitation_id', $exploitation->getKey())
            ->where('cycle_culture_id', $cycles[0]->getKey())
            ->firstOrFail();

        $ventes->creer([
            'partenaire_id' => $client->getKey(),
            'date_vente' => now()->toDateString(),
            'taux_tva' => 5.5,
            'notes' => 'Brouillon à valider manuellement.',
            'lignes' => [
                [
                    'lot_stock_id' => $lotBrouillon->getKey(),
                    'designation' => $cycles[0]->culture->nom.' (brouillon)',
                    'quantite' => 5000,
                    'unite' => 'kg',
                    'prix_unitaire' => 0.28,
                ],
            ],
        ], $user);
    }
}
