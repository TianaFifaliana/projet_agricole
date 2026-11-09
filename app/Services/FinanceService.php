<?php

namespace App\Services;

use App\Enums\CategorieDepense;
use App\Models\Depense;
use App\Models\Irrigation;
use App\Models\Scopes\ExploitationScope;
use App\Models\User;

class FinanceService
{
    public function calculerCoutIrrigation(float $volumeM3, int $dureeMinutes): float
    {
        $coutEnergie = $volumeM3 * (float) config('irrigation.cout_energie_par_m3', 0.15);
        $coutMainOeuvre = ($dureeMinutes / 60) * (float) config('irrigation.cout_main_oeuvre_par_heure', 20);

        return round($coutEnergie + $coutMainOeuvre, 2);
    }

    public function enregistrerDepenseIrrigation(
        Irrigation $irrigation,
        float $volumeM3,
        int $dureeMinutes,
        ?User $user = null,
    ): ?Depense {
        $montant = $this->calculerCoutIrrigation($volumeM3, $dureeMinutes);

        if ($montant <= 0) {
            return null;
        }

        $coutEnergie = round($volumeM3 * (float) config('irrigation.cout_energie_par_m3', 0.15), 2);
        $coutMainOeuvre = round(($dureeMinutes / 60) * (float) config('irrigation.cout_main_oeuvre_par_heure', 20), 2);

        return Depense::withoutGlobalScope(ExploitationScope::class)->create([
            'exploitation_id' => $irrigation->exploitation_id,
            'irrigation_id' => $irrigation->getKey(),
            'user_id' => $user?->getKey() ?? $irrigation->user_id,
            'date' => $irrigation->date->toDateString(),
            'categorie' => CategorieDepense::Irrigation,
            'libelle' => sprintf(
                'Irrigation du %s - campagne #%d',
                $irrigation->date->format('d/m/Y'),
                $irrigation->campaign_id,
            ),
            'montant' => $montant,
            'notes' => sprintf(
                'Énergie pompe : %s ; Main-d\'œuvre : %s',
                number_format($coutEnergie, 2, ',', ' '),
                number_format($coutMainOeuvre, 2, ',', ' '),
            ),
        ]);
    }
}
