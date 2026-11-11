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
        $soavina = Exploitation::where('code', 'EXP-001')->firstOrFail();
        $anjohory = Exploitation::where('code', 'EXP-002')->firstOrFail();

        $this->creerCultures($soavina);
        $this->creerCultures($anjohory);

        $this->creerIntrants($soavina);
        $this->creerIntrants($anjohory);

        $this->creerParcelles($soavina);
        $this->creerParcelles($anjohory);

        $this->creerPartenaires($soavina);
        $this->creerPartenaires($anjohory);

        $this->creerAnimaux($soavina);
        $this->creerAnimaux($anjohory);
    }

    private function creerCultures(Exploitation $exploitation): void
    {
        $cultures = $exploitation->code === 'EXP-001'
            ? [
                ['nom' => 'Riz irrigué', 'variete' => 'Soamalandy', 'duree_cycle_jours' => 140, 'rendement_cible_tha' => 5.50],
                ['nom' => 'Riz irrigué', 'variete' => 'Sekladen', 'duree_cycle_jours' => 120, 'rendement_cible_tha' => 5.00],
                ['nom' => 'Riz irrigué', 'variete' => 'Fofifa 161', 'duree_cycle_jours' => 135, 'rendement_cible_tha' => 6.00],
                ['nom' => 'Riz irrigué', 'variete' => 'Rojofotsy', 'duree_cycle_jours' => 110, 'rendement_cible_tha' => 3.50],
                ['nom' => 'Riz traditionnel', 'variete' => 'Makalioka', 'duree_cycle_jours' => 150, 'rendement_cible_tha' => 2.50],
            ]
            : [
                ['nom' => 'Riz irrigué', 'variete' => 'X265', 'duree_cycle_jours' => 125, 'rendement_cible_tha' => 5.50],
                ['nom' => 'Riz irrigué', 'variete' => 'BG 90-2', 'duree_cycle_jours' => 130, 'rendement_cible_tha' => 5.20],
                ['nom' => 'Riz pluvial', 'variete' => 'Rojofotsy', 'duree_cycle_jours' => 110, 'rendement_cible_tha' => 2.50],
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
        $intrants = $exploitation->code === 'EXP-001'
            ? [
                ['nom' => 'Semence riz Soamalandy certifiée', 'categorie' => 'semence', 'unite' => 'kg', 'seuil_alerte' => 500, 'prix_unitaire' => 3500],
                ['nom' => 'Engrais NPK 11-22-16', 'categorie' => 'engrais', 'unite' => 'kg', 'seuil_alerte' => 400, 'prix_unitaire' => 4500],
                ['nom' => 'Urée 46%', 'categorie' => 'engrais', 'unite' => 'kg', 'seuil_alerte' => 300, 'prix_unitaire' => 3800],
                ['nom' => 'Fumier de zébu décomposé', 'categorie' => 'engrais', 'unite' => 'kg', 'seuil_alerte' => 1500, 'prix_unitaire' => 250],
                ['nom' => 'Herbicide butachlore', 'categorie' => 'phytosanitaire', 'unite' => 'L', 'seuil_alerte' => 20, 'prix_unitaire' => 48000],
                ['nom' => 'Gasoil pompe', 'categorie' => 'autre', 'unite' => 'L', 'seuil_alerte' => 100, 'prix_unitaire' => 8500],
            ]
            : [
                ['nom' => 'Semence riz X265 certifiée', 'categorie' => 'semence', 'unite' => 'kg', 'seuil_alerte' => 250, 'prix_unitaire' => 3200],
                ['nom' => 'Engrais NPK 11-22-16', 'categorie' => 'engrais', 'unite' => 'kg', 'seuil_alerte' => 350, 'prix_unitaire' => 4500],
                ['nom' => 'Urée 46%', 'categorie' => 'engrais', 'unite' => 'kg', 'seuil_alerte' => 250, 'prix_unitaire' => 3800],
                ['nom' => 'Fumier de zébu décomposé', 'categorie' => 'engrais', 'unite' => 'kg', 'seuil_alerte' => 1200, 'prix_unitaire' => 250],
                ['nom' => 'Herbicide 2,4-D', 'categorie' => 'phytosanitaire', 'unite' => 'L', 'seuil_alerte' => 15, 'prix_unitaire' => 42000],
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
                ['code' => 'RZ-01', 'nom' => 'Tanety Ambony', 'superficie_ha' => 12.00, 'type_sol' => 'argilo-limoneux', 'latitude' => -17.8281, 'longitude' => 48.4258],
                ['code' => 'RZ-02', 'nom' => 'Bas-fond Andranakoho', 'superficie_ha' => 9.50, 'type_sol' => 'hydromorphe', 'latitude' => -17.8350, 'longitude' => 48.4310],
                ['code' => 'RZ-03', 'nom' => 'Plaine de Bevary', 'superficie_ha' => 8.00, 'type_sol' => 'argileux', 'latitude' => -17.8189, 'longitude' => 48.4402],
                ['code' => 'RZ-04', 'nom' => 'Ambohidroa', 'superficie_ha' => 6.50, 'type_sol' => 'limoneux', 'latitude' => -17.8405, 'longitude' => 48.4177],
                ['code' => 'RZ-05', 'nom' => 'Antsirakambe Est', 'superficie_ha' => 5.00, 'type_sol' => 'sableux', 'latitude' => -17.8120, 'longitude' => 48.4530],
            ]
            : [
                ['code' => 'RZ-01', 'nom' => 'Matiovaina Nord', 'superficie_ha' => 15.00, 'type_sol' => 'argileux', 'latitude' => -16.1105, 'longitude' => 46.6330],
                ['code' => 'RZ-02', 'nom' => 'Ankilivalu', 'superficie_ha' => 10.00, 'type_sol' => 'limono-argileux', 'latitude' => -16.1180, 'longitude' => 46.6410],
                ['code' => 'RZ-03', 'nom' => 'Rive Betsiboka', 'superficie_ha' => 6.50, 'type_sol' => 'alluvial', 'latitude' => -16.0995, 'longitude' => 46.6520],
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
        $partenaires = $exploitation->code === 'EXP-001'
            ? [
                ['type' => 'client', 'nom' => 'Moulin Rizicole Soatsara', 'email' => 'achats@moulin-soatsara.test', 'telephone' => '034 12 345 01', 'adresse' => 'Quartier Antsirabe Be, Ambatondrazaka'],
                ['type' => 'fournisseur', 'nom' => 'Appro Mahaleo', 'email' => 'contact@appro-mahaleo.test', 'telephone' => '033 22 145 08', 'adresse' => 'Zone industrielle, Moramanga'],
                ['type' => 'les_deux', 'nom' => 'Négoce Vary Be Antsirabe', 'email' => 'negoce@varybe-antsirabe.test', 'telephone' => '032 55 901 44', 'adresse' => 'Avenue de l\'Indépendance, Antsirabe'],
            ]
            : [
                ['type' => 'client', 'nom' => 'Meunerie Betsiboka Sud', 'email' => 'commandes@meunerie-betsiboka.test', 'telephone' => '034 90 555 12', 'adresse' => 'Boulevard Mangovanomby, Mahajanga'],
                ['type' => 'fournisseur', 'nom' => 'Agro-Appro Mitsinjo', 'email' => 'ventes@agroappro-mitsinjo.test', 'telephone' => '033 44 220 76', 'adresse' => 'RN 4, Mitsinjo'],
                ['type' => 'les_deux', 'nom' => 'Coopérative Vary Itasy', 'email' => 'bureau@vary-itasy.test', 'telephone' => '032 18 660 90', 'adresse' => 'Rue du Marché, Miarinarivo'],
            ];

        foreach ($partenaires as $partenaire) {
            Partenaire::create($partenaire + [
                'exploitation_id' => $exploitation->getKey(),
                'actif' => true,
            ]);
        }
    }

    private function creerAnimaux(Exploitation $exploitation): void
    {
        $animaux = $exploitation->code === 'EXP-001'
            ? [
                ['identifiant' => 'AN-00001', 'nom' => 'Tsara', 'espece' => 'bovin', 'race' => 'Zébu malgache', 'sexe' => 'F', 'poids_kg' => 420],
                ['identifiant' => 'AN-00002', 'nom' => 'Vorona', 'espece' => 'bovin', 'race' => 'Zébu malgache', 'sexe' => 'M', 'poids_kg' => 520],
                ['identifiant' => 'AN-00003', 'nom' => 'Hazo', 'espece' => 'bovin', 'race' => 'Zébu malgache', 'sexe' => 'M', 'poids_kg' => 480],
            ]
            : [
                ['identifiant' => 'AN-00001', 'nom' => 'Kely', 'espece' => 'canard', 'race' => 'Khaki Campbell', 'sexe' => 'M', 'poids_kg' => 2.2],
                ['identifiant' => 'AN-00002', 'nom' => 'Vavy', 'espece' => 'canard', 'race' => 'Khaki Campbell', 'sexe' => 'F', 'poids_kg' => 2.5],
                ['identifiant' => 'AN-00003', 'nom' => 'Fotsy', 'espece' => 'canard', 'race' => 'Khaki Campbell', 'sexe' => 'F', 'poids_kg' => 2.1],
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
