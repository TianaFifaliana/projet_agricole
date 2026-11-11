<?php

namespace Database\Seeders;

use App\Enums\CategorieDepense;
use App\Enums\TypeIntervention;
use App\Models\Culture;
use App\Models\CycleCulture;
use App\Models\Depense;
use App\Models\Exploitation;
use App\Models\Intervention;
use App\Models\Intrant;
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
        $soavina = Exploitation::where('code', 'EXP-001')->firstOrFail();
        $anjohory = Exploitation::where('code', 'EXP-002')->firstOrFail();

        $responsableSoavina = $this->user('responsable@agrisaas.test');
        $agentSoavina = $this->user('agent@agrisaas.test');
        $responsableAnjohory = $this->user('responsable.anjohory@agrisaas.test');
        $agentAnjohory = $this->user('agent.anjohory@agrisaas.test');

        $cycleSoamalandy = $this->creerCyclesSoavina($cycles, $soavina, $agentSoavina);
        $cycleBg = $this->creerCyclesAnjohory($cycles, $anjohory, $agentAnjohory);

        $this->creerStockSoavina($soavina, $stock, $responsableSoavina, $agentSoavina);
        $this->creerStockAnjohory($anjohory, $stock, $responsableAnjohory, $agentAnjohory);

        $this->creerVentesSoavina($soavina, $ventes, $responsableSoavina, $agentSoavina, $cycleSoamalandy['fofifa'], $cycleSoamalandy['rojofotsy']);
        $this->creerVentesAnjohory($anjohory, $ventes, $responsableAnjohory, $agentAnjohory, $cycleBg);

        $this->creerDepensesSoavina($soavina, $responsableSoavina);
        $this->creerDepensesAnjohory($anjohory, $responsableAnjohory);
    }

    /**
     * @return array<string, CycleCulture>
     */
    private function creerCyclesSoavina(CycleCultureService $cycles, Exploitation $exploitation, User $agent): array
    {
        $cycleSoamalandy = $cycles->creer([
            'parcelle_id' => $this->parcelle($exploitation, 'RZ-01')->getKey(),
            'culture_id' => $this->culture($exploitation, 'Riz irrigué', 'Soamalandy')->getKey(),
            'campagne' => '2026/27',
            'date_semis' => '2026-10-28',
            'date_recolte_prevue' => '2027-03-16',
            'surface_ha' => 11.50,
            'notes' => 'Saison des pluies 2026-2027, repiquage après pépinière.',
        ]);

        $cycleSekladen = $cycles->creer([
            'parcelle_id' => $this->parcelle($exploitation, 'RZ-02')->getKey(),
            'culture_id' => $this->culture($exploitation, 'Riz irrigué', 'Sekladen')->getKey(),
            'campagne' => '2026/27',
            'date_semis' => '2026-11-05',
            'date_recolte_prevue' => '2027-03-05',
            'surface_ha' => 9.00,
            'notes' => 'Saison des pluies 2026-2027, culture de tête sur bas-fond.',
        ]);

        $cycleFofifa = $cycles->creer([
            'parcelle_id' => $this->parcelle($exploitation, 'RZ-03')->getKey(),
            'culture_id' => $this->culture($exploitation, 'Riz irrigué', 'Fofifa 161')->getKey(),
            'campagne' => '2026',
            'date_semis' => '2026-05-20',
            'date_recolte_prevue' => '2026-10-02',
            'surface_ha' => 7.50,
            'notes' => 'Contre-saison 2026 alimentée par le canal Andranomandry.',
        ]);

        $cycleRojofotsy = $cycles->creer([
            'parcelle_id' => $this->parcelle($exploitation, 'RZ-04')->getKey(),
            'culture_id' => $this->culture($exploitation, 'Riz irrigué', 'Rojofotsy')->getKey(),
            'campagne' => '2026',
            'date_semis' => '2026-06-01',
            'date_recolte_prevue' => '2026-09-19',
            'surface_ha' => 6.00,
            'notes' => 'Contre-saison 2026, variété à cycle court.',
        ]);

        $cycles->creer([
            'parcelle_id' => $this->parcelle($exploitation, 'RZ-05')->getKey(),
            'culture_id' => $this->culture($exploitation, 'Riz traditionnel', 'Makalioka')->getKey(),
            'campagne' => '2026/27',
            'date_semis' => '2026-10-25',
            'date_recolte_prevue' => '2027-03-24',
            'surface_ha' => 4.50,
            'notes' => 'Saison des pluies 2026-2027, variété aromatique traditionnelle.',
        ]);

        $this->intervention($exploitation, 'RZ-01', $cycleSoamalandy, $agent, TypeIntervention::Semis, '2026-10-28', 1150, 'kg', 3450000, 'Repiquage des plants de pépinière (11,5 ha)');
        $this->intervention($exploitation, 'RZ-01', $cycleSoamalandy, $agent, TypeIntervention::Fertilisation, '2026-11-02', 450, 'kg', 2025000, 'NPK 11-22-16 de couverture');
        $this->intervention($exploitation, 'RZ-01', $cycleSoamalandy, $agent, TypeIntervention::Traitement, '2026-11-06', 12, 'L', 576000, 'Herbicide butachlore post-repiquage');
        $this->intervention($exploitation, 'RZ-02', $cycleSekladen, $agent, TypeIntervention::Semis, '2026-11-05', null, null, 2700000, 'Repiquage sur le bas-fond Andranakoho (9 ha)');
        $this->intervention($exploitation, 'RZ-03', $cycleFofifa, $agent, TypeIntervention::Fertilisation, '2026-06-10', 300, 'kg', 1140000, 'Urée 46% de couverture');
        $this->intervention($exploitation, 'RZ-03', $cycleFofifa, $agent, TypeIntervention::Traitement, '2026-06-25', 9, 'L', 432000, 'Herbicide butachlore');
        $this->intervention($exploitation, 'RZ-04', $cycleRojofotsy, $agent, TypeIntervention::Fertilisation, '2026-07-05', 200, 'kg', 900000, 'Complément NPK sur Rojofotsy');

        $cycles->enregistrerRecolte($cycleFofifa, [
            'quantite_kg' => 46400,
            'date_recolte' => '2026-10-04',
            'description' => 'Récolte paddy Fofifa 161, battage mécanisé.',
        ], $agent);

        $cycles->enregistrerRecolte($cycleRojofotsy, [
            'quantite_kg' => 20400,
            'date_recolte' => '2026-09-20',
            'description' => 'Récolte paddy Rojofotsy après drainage.',
        ], $agent);

        return [
            'soamalandy' => $cycleSoamalandy,
            'fofifa' => $cycleFofifa,
            'rojofotsy' => $cycleRojofotsy,
        ];
    }

    private function creerCyclesAnjohory(CycleCultureService $cycles, Exploitation $exploitation, User $agent): CycleCulture
    {
        $cycleX265 = $cycles->creer([
            'parcelle_id' => $this->parcelle($exploitation, 'RZ-01')->getKey(),
            'culture_id' => $this->culture($exploitation, 'Riz irrigué', 'X265')->getKey(),
            'campagne' => '2026/27',
            'date_semis' => '2026-11-02',
            'date_recolte_prevue' => '2027-03-06',
            'surface_ha' => 14.50,
            'notes' => 'Saison des pluies 2026-2027, semis direct sur Matiovaina.',
        ]);

        $cycleBg = $cycles->creer([
            'parcelle_id' => $this->parcelle($exploitation, 'RZ-02')->getKey(),
            'culture_id' => $this->culture($exploitation, 'Riz irrigué', 'BG 90-2')->getKey(),
            'campagne' => '2026',
            'date_semis' => '2026-04-25',
            'date_recolte_prevue' => '2026-09-02',
            'surface_ha' => 9.50,
            'notes' => 'Contre-saison 2026, maîtrise totale de l\'eau sur Ankilivalu.',
        ]);

        $this->intervention($exploitation, 'RZ-01', $cycleX265, $agent, TypeIntervention::Semis, '2026-11-02', 1160, 'kg', 725000, 'Semis direct X265 (14,5 ha)');
        $this->intervention($exploitation, 'RZ-01', $cycleX265, $agent, TypeIntervention::Fertilisation, '2026-11-08', 500, 'kg', 2250000, 'NPK 11-22-16 de fond');
        $this->intervention($exploitation, 'RZ-02', $cycleBg, $agent, TypeIntervention::Fertilisation, '2026-05-15', 350, 'kg', 1330000, 'Urée 46% de couverture');
        $this->intervention($exploitation, 'RZ-02', $cycleBg, $agent, TypeIntervention::Traitement, '2026-06-01', 8, 'L', 336000, 'Herbicide 2,4-D');

        $cycles->enregistrerRecolte($cycleBg, [
            'quantite_kg' => 48450,
            'date_recolte' => '2026-09-04',
            'description' => 'Récolte paddy BG 90-2, séchage sur aire.',
        ], $agent);

        return $cycleBg;
    }

    private function creerStockSoavina(
        Exploitation $exploitation,
        StockService $stock,
        User $responsable,
        User $agent,
    ): void {
        $semence = $this->intrant($exploitation, 'Semence riz Soamalandy certifiée');
        $npk = $this->intrant($exploitation, 'Engrais NPK 11-22-16');
        $uree = $this->intrant($exploitation, 'Urée 46%');
        $fumier = $this->intrant($exploitation, 'Fumier de zébu décomposé');
        $herbicide = $this->intrant($exploitation, 'Herbicide butachlore');
        $gasoil = $this->intrant($exploitation, 'Gasoil pompe');

        $entrees = [
            ['code' => 'ACH-SEM-'.$exploitation->getKey(), 'intrant' => $semence, 'quantite' => 2400, 'date' => '2026-10-08', 'motif' => 'Achat semence certifiée pour la saison des pluies'],
            ['code' => 'ACH-NPK-'.$exploitation->getKey(), 'intrant' => $npk, 'quantite' => 1200, 'date' => '2026-09-12', 'motif' => 'Achat engrais de couverture'],
            ['code' => 'ACH-UREE-'.$exploitation->getKey(), 'intrant' => $uree, 'quantite' => 800, 'date' => '2026-05-02', 'motif' => 'Achat urée pour la contre-saison'],
            ['code' => 'ACH-FUM-'.$exploitation->getKey(), 'intrant' => $fumier, 'quantite' => 6000, 'date' => '2026-04-18', 'motif' => 'Fumier de zébu livré en charrettes'],
            ['code' => 'ACH-HERB-'.$exploitation->getKey(), 'intrant' => $herbicide, 'quantite' => 60, 'date' => '2026-04-28', 'motif' => 'Achat herbicide pour deux saisons'],
            ['code' => 'ACH-GAS-'.$exploitation->getKey(), 'intrant' => $gasoil, 'quantite' => 400, 'date' => '2026-10-01', 'motif' => 'Achat gasoil pour la motoculture'],
        ];

        foreach ($entrees as $entree) {
            $stock->entrer(
                exploitationId: $exploitation->getKey(),
                codeLot: $entree['code'],
                quantite: $entree['quantite'],
                unite: $entree['intrant']->unite,
                attributes: [
                    'intrant_id' => $entree['intrant']->getKey(),
                    'date_entree' => $entree['date'],
                    'origine' => 'achat',
                    'prix_unitaire' => $entree['intrant']->prix_unitaire,
                    'motif' => $entree['motif'],
                ],
                user: $responsable,
            );
        }

        $stock->sortir(
            lot: $this->lot($exploitation, 'ACH-SEM-'.$exploitation->getKey()),
            quantite: 1150,
            motif: 'Repiquage campagne 2026/27 - rizière RZ-01',
            user: $agent,
        );

        $stock->sortir(
            lot: $this->lot($exploitation, 'ACH-SEM-'.$exploitation->getKey()),
            quantite: 850,
            motif: 'Repiquage campagne 2026/27 - rizière RZ-02',
            user: $agent,
        );

        $stock->sortir(
            lot: $this->lot($exploitation, 'ACH-NPK-'.$exploitation->getKey()),
            quantite: 450,
            motif: 'Fertilisation rizière RZ-01',
            user: $agent,
        );

        $stock->sortir(
            lot: $this->lot($exploitation, 'ACH-NPK-'.$exploitation->getKey()),
            quantite: 200,
            motif: 'Complément NPK rizière RZ-04',
            user: $agent,
        );

        $stock->sortir(
            lot: $this->lot($exploitation, 'ACH-UREE-'.$exploitation->getKey()),
            quantite: 300,
            motif: 'Urée de couverture rizière RZ-03',
            user: $agent,
        );

        $stock->sortir(
            lot: $this->lot($exploitation, 'ACH-FUM-'.$exploitation->getKey()),
            quantite: 3500,
            motif: 'Épandage avant repiquage rizière RZ-03',
            user: $agent,
        );

        $stock->sortir(
            lot: $this->lot($exploitation, 'ACH-HERB-'.$exploitation->getKey()),
            quantite: 12,
            motif: 'Désherbage rizière RZ-01',
            user: $agent,
        );

        $stock->sortir(
            lot: $this->lot($exploitation, 'ACH-HERB-'.$exploitation->getKey()),
            quantite: 9,
            motif: 'Désherbage rizière RZ-03',
            user: $agent,
        );

        $stock->sortir(
            lot: $this->lot($exploitation, 'ACH-GAS-'.$exploitation->getKey()),
            quantite: 150,
            motif: 'Carburant motoculteur et tournées de bourrage',
            user: $agent,
        );
    }

    private function creerStockAnjohory(
        Exploitation $exploitation,
        StockService $stock,
        User $responsable,
        User $agent,
    ): void {
        $semence = $this->intrant($exploitation, 'Semence riz X265 certifiée');
        $npk = $this->intrant($exploitation, 'Engrais NPK 11-22-16');
        $uree = $this->intrant($exploitation, 'Urée 46%');
        $fumier = $this->intrant($exploitation, 'Fumier de zébu décomposé');
        $herbicide = $this->intrant($exploitation, 'Herbicide 2,4-D');

        $entrees = [
            ['code' => 'ACH-SEM-'.$exploitation->getKey(), 'intrant' => $semence, 'quantite' => 1600, 'date' => '2026-10-03', 'motif' => 'Achat semence X265 pour le semis direct'],
            ['code' => 'ACH-NPK-'.$exploitation->getKey(), 'intrant' => $npk, 'quantite' => 1600, 'date' => '2026-04-10', 'motif' => 'Achat engrais de fond'],
            ['code' => 'ACH-UREE-'.$exploitation->getKey(), 'intrant' => $uree, 'quantite' => 700, 'date' => '2026-04-08', 'motif' => 'Achat urée pour la contre-saison'],
            ['code' => 'ACH-FUM-'.$exploitation->getKey(), 'intrant' => $fumier, 'quantite' => 4500, 'date' => '2026-04-30', 'motif' => 'Fumier de zébu pour la préparation des sols'],
            ['code' => 'ACH-HERB-'.$exploitation->getKey(), 'intrant' => $herbicide, 'quantite' => 25, 'date' => '2026-05-20', 'motif' => 'Achat herbicide 2,4-D'],
        ];

        foreach ($entrees as $entree) {
            $stock->entrer(
                exploitationId: $exploitation->getKey(),
                codeLot: $entree['code'],
                quantite: $entree['quantite'],
                unite: $entree['intrant']->unite,
                attributes: [
                    'intrant_id' => $entree['intrant']->getKey(),
                    'date_entree' => $entree['date'],
                    'origine' => 'achat',
                    'prix_unitaire' => $entree['intrant']->prix_unitaire,
                    'motif' => $entree['motif'],
                ],
                user: $responsable,
            );
        }

        $stock->sortir(
            lot: $this->lot($exploitation, 'ACH-SEM-'.$exploitation->getKey()),
            quantite: 1160,
            motif: 'Semis direct campagne 2026/27 - rizière RZ-01',
            user: $agent,
        );

        $stock->sortir(
            lot: $this->lot($exploitation, 'ACH-NPK-'.$exploitation->getKey()),
            quantite: 500,
            motif: 'Engrais de fond contre-saison 2026 - rizière RZ-02',
            user: $agent,
        );

        $stock->sortir(
            lot: $this->lot($exploitation, 'ACH-NPK-'.$exploitation->getKey()),
            quantite: 500,
            motif: 'Engrais de fond campagne 2026/27 - rizière RZ-01',
            user: $agent,
        );

        $stock->sortir(
            lot: $this->lot($exploitation, 'ACH-UREE-'.$exploitation->getKey()),
            quantite: 350,
            motif: 'Urée de couverture rizière RZ-02',
            user: $agent,
        );

        $stock->sortir(
            lot: $this->lot($exploitation, 'ACH-FUM-'.$exploitation->getKey()),
            quantite: 3000,
            motif: 'Épandage avant semis rizière RZ-02',
            user: $agent,
        );

        $stock->sortir(
            lot: $this->lot($exploitation, 'ACH-HERB-'.$exploitation->getKey()),
            quantite: 8,
            motif: 'Désherbage rizière RZ-02',
            user: $agent,
        );
    }

    private function creerVentesSoavina(
        Exploitation $exploitation,
        VenteService $ventes,
        User $responsable,
        User $agent,
        CycleCulture $cycleFofifa,
        CycleCulture $cycleRojofotsy,
    ): void {
        $client = $this->partenaire($exploitation, 'Moulin Rizicole Soatsara');

        $lotFofifa = LotStock::withoutGlobalScopes()
            ->where('exploitation_id', $exploitation->getKey())
            ->where('cycle_culture_id', $cycleFofifa->getKey())
            ->firstOrFail();

        $lotRojofotsy = LotStock::withoutGlobalScopes()
            ->where('exploitation_id', $exploitation->getKey())
            ->where('cycle_culture_id', $cycleRojofotsy->getKey())
            ->firstOrFail();

        $vente = $ventes->creer([
            'partenaire_id' => $client->getKey(),
            'date_vente' => '2026-10-20',
            'taux_tva' => 20,
            'notes' => 'Livraison paddy Fofifa 161, contre-saison 2026.',
            'lignes' => [
                [
                    'lot_stock_id' => $lotFofifa->getKey(),
                    'designation' => 'Paddy Fofifa 161 - rizière RZ-03',
                    'quantite' => 18000,
                    'unite' => 'kg',
                    'prix_unitaire' => 2400,
                ],
            ],
        ], $responsable);

        $ventes->valider($vente, $responsable);

        $ventes->creer([
            'partenaire_id' => $client->getKey(),
            'date_vente' => '2026-11-08',
            'taux_tva' => 20,
            'notes' => 'Brouillon à valider par le responsable.',
            'lignes' => [
                [
                    'lot_stock_id' => $lotRojofotsy->getKey(),
                    'designation' => 'Paddy Rojofotsy - rizière RZ-04',
                    'quantite' => 6000,
                    'unite' => 'kg',
                    'prix_unitaire' => 2450,
                ],
            ],
        ], $agent);
    }

    private function creerVentesAnjohory(
        Exploitation $exploitation,
        VenteService $ventes,
        User $responsable,
        User $agent,
        CycleCulture $cycleBg,
    ): void {
        $client = $this->partenaire($exploitation, 'Meunerie Betsiboka Sud');

        $lotBg = LotStock::withoutGlobalScopes()
            ->where('exploitation_id', $exploitation->getKey())
            ->where('cycle_culture_id', $cycleBg->getKey())
            ->firstOrFail();

        $vente = $ventes->creer([
            'partenaire_id' => $client->getKey(),
            'date_vente' => '2026-09-25',
            'taux_tva' => 20,
            'notes' => 'Livraison paddy BG 90-2 après séchage sur aire.',
            'lignes' => [
                [
                    'lot_stock_id' => $lotBg->getKey(),
                    'designation' => 'Paddy BG 90-2 - rizière Ankilivalu',
                    'quantite' => 22000,
                    'unite' => 'kg',
                    'prix_unitaire' => 2300,
                ],
            ],
        ], $responsable);

        $ventes->valider($vente, $responsable);

        $ventes->creer([
            'partenaire_id' => $client->getKey(),
            'date_vente' => '2026-11-05',
            'taux_tva' => 20,
            'notes' => 'Brouillon en attente de la validation du responsable.',
            'lignes' => [
                [
                    'lot_stock_id' => $lotBg->getKey(),
                    'designation' => 'Paddy BG 90-2 - rizière Ankilivalu',
                    'quantite' => 8000,
                    'unite' => 'kg',
                    'prix_unitaire' => 2350,
                ],
            ],
        ], $agent);
    }

    private function creerDepensesSoavina(Exploitation $exploitation, User $responsable): void
    {
        $depenses = [
            ['date' => '2026-04-18', 'categorie' => CategorieDepense::Autre, 'libelle' => 'Achat fumier de zébu - 6 000 kg', 'montant' => 1500000, 'notes' => 'Livré en charrettes par un éleveur voisin.'],
            ['date' => '2026-05-02', 'categorie' => CategorieDepense::Intrant, 'libelle' => 'Achat urée 46% - 800 kg', 'montant' => 3040000, 'notes' => null],
            ['date' => '2026-09-12', 'categorie' => CategorieDepense::Intrant, 'libelle' => 'Achat NPK 11-22-16 - 1 200 kg', 'montant' => 5400000, 'notes' => null],
            ['date' => '2026-10-08', 'categorie' => CategorieDepense::Intrant, 'libelle' => 'Achat semence Soamalandy certifiée - 2 400 kg', 'montant' => 8400000, 'notes' => 'Semence certifiée FOFIFA Ambatondrazaka.'],
            ['date' => '2026-10-28', 'categorie' => CategorieDepense::MainOeuvre, 'libelle' => 'Main-d\'œuvre repiquage RZ-01 - 11,5 ha', 'montant' => 3450000, 'notes' => null],
            ['date' => '2026-11-06', 'categorie' => CategorieDepense::MainOeuvre, 'libelle' => 'Main-d\'œuvre désherbage RZ-01', 'montant' => 480000, 'notes' => null],
        ];

        foreach ($depenses as $depense) {
            Depense::create($depense + [
                'exploitation_id' => $exploitation->getKey(),
                'user_id' => $responsable->getKey(),
                'irrigation_id' => null,
            ]);
        }
    }

    private function creerDepensesAnjohory(Exploitation $exploitation, User $responsable): void
    {
        $depenses = [
            ['date' => '2026-04-08', 'categorie' => CategorieDepense::Intrant, 'libelle' => 'Achat urée 46% - 700 kg', 'montant' => 2660000, 'notes' => null],
            ['date' => '2026-04-10', 'categorie' => CategorieDepense::Intrant, 'libelle' => 'Achat NPK 11-22-16 - 1 600 kg', 'montant' => 7200000, 'notes' => null],
            ['date' => '2026-04-30', 'categorie' => CategorieDepense::Intrant, 'libelle' => 'Achat fumier de zébu - 4 500 kg', 'montant' => 1125000, 'notes' => null],
            ['date' => '2026-04-30', 'categorie' => CategorieDepense::MainOeuvre, 'libelle' => 'Main-d\'œuvre repiquage RZ-02 - 9,5 ha', 'montant' => 2850000, 'notes' => null],
            ['date' => '2026-10-03', 'categorie' => CategorieDepense::Intrant, 'libelle' => 'Achat semence X265 certifiée - 1 600 kg', 'montant' => 5120000, 'notes' => null],
            ['date' => '2026-09-26', 'categorie' => CategorieDepense::Autre, 'libelle' => 'Transport paddy vers la meunerie de Mahajanga', 'montant' => 600000, 'notes' => 'Deux voyages camion.'],
        ];

        foreach ($depenses as $depense) {
            Depense::create($depense + [
                'exploitation_id' => $exploitation->getKey(),
                'user_id' => $responsable->getKey(),
                'irrigation_id' => null,
            ]);
        }
    }

    private function user(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    private function culture(Exploitation $exploitation, string $nom, string $variete): Culture
    {
        return Culture::withoutGlobalScopes()
            ->where('exploitation_id', $exploitation->getKey())
            ->where('nom', $nom)
            ->where('variete', $variete)
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

    private function partenaire(Exploitation $exploitation, string $nom): Partenaire
    {
        return Partenaire::withoutGlobalScopes()
            ->where('exploitation_id', $exploitation->getKey())
            ->where('nom', $nom)
            ->firstOrFail();
    }

    private function lot(Exploitation $exploitation, string $codeLot): LotStock
    {
        return LotStock::withoutGlobalScopes()
            ->where('exploitation_id', $exploitation->getKey())
            ->where('code_lot', $codeLot)
            ->firstOrFail();
    }

    private function intervention(
        Exploitation $exploitation,
        string $parcelleCode,
        CycleCulture $cycle,
        User $operateur,
        TypeIntervention $type,
        string $date,
        ?float $quantite,
        ?string $unite,
        ?float $cout,
        ?string $description,
    ): Intervention {
        $parcelle = $this->parcelle($exploitation, $parcelleCode);

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
}
