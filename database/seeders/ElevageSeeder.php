<?php

namespace Database\Seeders;

use App\Models\Animal;
use App\Models\EvenementSanitaire;
use App\Models\Exploitation;
use Illuminate\Database\Seeder;

class ElevageSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Exploitation::all() as $exploitation) {
            $animaux = Animal::withoutGlobalScopes()
                ->where('exploitation_id', $exploitation->getKey())
                ->orderBy('identifiant')
                ->take(2)
                ->get();

            foreach ($animaux as $animal) {
                EvenementSanitaire::create([
                    'exploitation_id' => $exploitation->getKey(),
                    'animal_id' => $animal->getKey(),
                    'type' => 'vaccination',
                    'date_evenement' => now()->subDays(20)->toDateString(),
                    'medicament' => 'Vaccin multivalent',
                    'traitement' => '2 ml',
                    'date_rappel' => now()->addDays(10)->toDateString(),
                    'cout' => 45.00,
                    'observations' => 'Dr Lefèvre — Rappel annuel.',
                ]);

                EvenementSanitaire::create([
                    'exploitation_id' => $exploitation->getKey(),
                    'animal_id' => $animal->getKey(),
                    'type' => 'vermifuge',
                    'date_evenement' => now()->subDays(5)->toDateString(),
                    'medicament' => 'Vermifuge large spectre',
                    'traitement' => '10 ml',
                    'date_rappel' => now()->addDays(14)->toDateString(),
                    'cout' => 18.00,
                    'observations' => null,
                ]);
            }
        }
    }
}
