<?php

namespace Database\Seeders;

use App\Models\Animal;
use App\Models\Culture;
use App\Models\Exploitation;
use App\Models\Intrant;
use App\Models\Parcelle;
use App\Models\Partenaire;
use Illuminate\Database\Seeder;

class ReferentielSeeder extends Seeder
{
    public function run(): void
    {
        $tilleuls = Exploitation::where('code', 'EXP-001')->firstOrFail();
        $val = Exploitation::where('code', 'EXP-002')->firstOrFail();

        $this->creerCultures($tilleuls);
        $this->creerCultures($val);

        $this->creerIntrants($tilleuls);
        $this->creerIntrants($val);

        $this->creerParcelles($tilleuls);
        $this->creerParcelles($val);

        $this->creerPartenaires($tilleuls);
        $this->creerPartenaires($val);

        $this->creerAnimaux($tilleuls);
        $this->creerAnimaux($val);
    }

    private function creerCultures(Exploitation $exploitation): void
    {
        $cultures = [
            ['nom' => 'Blé tendre', 'variete' => 'Apache', 'duree_cycle_jours' => 240, 'rendement_cible_tha' => 7.50],
            ['nom' => 'Maïs grain', 'variete' => 'Pioneer', 'duree_cycle_jours' => 150, 'rendement_cible_tha' => 10.00],
            ['nom' => 'Orge', 'variete' => 'Étincel', 'duree_cycle_jours' => 210, 'rendement_cible_tha' => 6.00],
            ['nom' => 'Colza', 'variete' => 'DK Exsteel', 'duree_cycle_jours' => 280, 'rendement_cible_tha' => 4.00],
            ['nom' => 'Prairie permanente', 'variete' => null, 'duree_cycle_jours' => 365, 'rendement_cible_tha' => null],
        ];

        foreach ($cultures as $culture) {
            Culture::create($culture + [
                'exploitation_id' => $exploitation->getKey(),
                'actif' => true,
            ]);
        }
    }

    private function creerIntrants(Exploitation $exploitation): void
    {
        $intrants = [
            ['nom' => 'Engrais NPK 15-15-15', 'categorie' => 'engrais', 'unite' => 'kg', 'seuil_alerte' => 500, 'prix_unitaire' => 0.45],
            ['nom' => 'Semence blé Apache', 'categorie' => 'semence', 'unite' => 'kg', 'seuil_alerte' => 200, 'prix_unitaire' => 0.35],
            ['nom' => 'Fongicide foliaire', 'categorie' => 'phytosanitaire', 'unite' => 'L', 'seuil_alerte' => 20, 'prix_unitaire' => 18.00],
            ['nom' => 'Aliment bovin', 'categorie' => 'aliment', 'unite' => 'kg', 'seuil_alerte' => 1000, 'prix_unitaire' => 0.30],
            ['nom' => 'Gasoil non routier', 'categorie' => 'autre', 'unite' => 'L', 'seuil_alerte' => 100, 'prix_unitaire' => 1.80],
        ];

        foreach ($intrants as $intrant) {
            Intrant::create($intrant + [
                'exploitation_id' => $exploitation->getKey(),
                'actif' => true,
            ]);
        }
    }

    private function creerParcelles(Exploitation $exploitation): void
    {
        $parcelles = $exploitation->code === 'EXP-001'
            ? [
                ['code' => 'P-01', 'nom' => 'Les Champs', 'superficie_ha' => 12.50, 'type_sol' => 'limoneux', 'latitude' => 43.6045, 'longitude' => 1.4442],
                ['code' => 'P-02', 'nom' => 'La Plaine', 'superficie_ha' => 8.00, 'type_sol' => 'argileux', 'latitude' => 43.6100, 'longitude' => 1.4500],
                ['code' => 'P-03', 'nom' => 'Le Bois', 'superficie_ha' => 6.50, 'type_sol' => 'calcaire', 'latitude' => 43.5980, 'longitude' => 1.4300],
                ['code' => 'P-04', 'nom' => 'La Source', 'superficie_ha' => 4.00, 'type_sol' => 'sableux', 'latitude' => 43.6200, 'longitude' => 1.4600],
            ]
            : [
                ['code' => 'P-01', 'nom' => 'Les Prés', 'superficie_ha' => 10.00, 'type_sol' => 'limoneux', 'latitude' => 43.9280, 'longitude' => 2.1480],
                ['code' => 'P-02', 'nom' => 'La Colline', 'superficie_ha' => 7.50, 'type_sol' => 'argileux', 'latitude' => 43.9300, 'longitude' => 2.1500],
            ];

        foreach ($parcelles as $parcelle) {
            Parcelle::create($parcelle + [
                'exploitation_id' => $exploitation->getKey(),
                'actif' => true,
            ]);
        }
    }

    private function creerPartenaires(Exploitation $exploitation): void
    {
        Partenaire::create([
            'exploitation_id' => $exploitation->getKey(),
            'type' => 'client',
            'nom' => 'Coopérative Agricole du Sud',
            'email' => 'achats@coop-sud.test',
            'telephone' => '05 00 00 00 01',
            'adresse' => '1 avenue de la Coopérative',
            'actif' => true,
        ]);

        Partenaire::create([
            'exploitation_id' => $exploitation->getKey(),
            'type' => 'fournisseur',
            'nom' => 'Semences & Co',
            'email' => 'contact@semences-co.test',
            'telephone' => '05 00 00 00 02',
            'adresse' => '2 rue des Graines',
            'actif' => true,
        ]);

        Partenaire::create([
            'exploitation_id' => $exploitation->getKey(),
            'type' => 'les_deux',
            'nom' => 'Négoce Dupont',
            'email' => 'contact@negoce-dupont.test',
            'telephone' => '05 00 00 00 03',
            'adresse' => '3 place du Marché',
            'actif' => true,
        ]);
    }

    private function creerAnimaux(Exploitation $exploitation): void
    {
        $animaux = $exploitation->code === 'EXP-001'
            ? [
                ['identifiant' => 'AN-00001', 'nom' => 'Belle', 'espece' => 'bovin', 'race' => 'Charolaise', 'sexe' => 'F', 'poids_kg' => 620],
                ['identifiant' => 'AN-00002', 'nom' => 'Noisette', 'espece' => 'bovin', 'race' => 'Limousine', 'sexe' => 'F', 'poids_kg' => 580],
                ['identifiant' => 'AN-00003', 'nom' => 'Tornado', 'espece' => 'bovin', 'race' => 'Charolaise', 'sexe' => 'M', 'poids_kg' => 780],
            ]
            : [
                ['identifiant' => 'AN-00001', 'nom' => 'Mistral', 'espece' => 'ovin', 'race' => 'Lacaune', 'sexe' => 'M', 'poids_kg' => 75],
                ['identifiant' => 'AN-00002', 'nom' => 'Flocon', 'espece' => 'ovin', 'race' => 'Lacaune', 'sexe' => 'F', 'poids_kg' => 62],
            ];

        foreach ($animaux as $animal) {
            Animal::create($animal + [
                'exploitation_id' => $exploitation->getKey(),
                'date_naissance' => now()->subYears(2)->toDateString(),
                'statut' => 'actif',
                'date_entree' => now()->subYears(2)->toDateString(),
                'date_sortie' => null,
            ]);
        }
    }
}
