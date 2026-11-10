<?php

namespace App\Services;

use App\Enums\CategorieDepense;
use App\Enums\StatutVente;
use App\Models\Depense;
use App\Models\Irrigation;
use App\Models\Scopes\ExploitationScope;
use App\Models\User;
use App\Models\Vente;
use App\Support\ExploitationCache;
use Illuminate\Support\Facades\Cache;

class FinanceService
{
    /**
     * @return array<string, float|null>
     */
    public function marge(int $exploitationId): array
    {
        return Cache::remember(
            ExploitationCache::financeMarginKey($exploitationId),
            now()->addMinutes(ExploitationCache::TTL_MINUTES),
            fn (): array => $this->calculerMarge($exploitationId),
        );
    }

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

    /**
     * @return array<string, float|null>
     */
    private function calculerMarge(int $exploitationId): array
    {
        $recettes = (float) Vente::withoutGlobalScope(ExploitationScope::class)
            ->where('exploitation_id', $exploitationId)
            ->where('statut', StatutVente::Validee->value)
            ->sum('total_ht');

        $depenses = (float) Depense::withoutGlobalScope(ExploitationScope::class)
            ->where('exploitation_id', $exploitationId)
            ->sum('montant');

        $marge = round($recettes - $depenses, 2);

        return [
            'recettes_ht' => round($recettes, 2),
            'depenses' => round($depenses, 2),
            'marge' => $marge,
            'taux_marge' => $recettes > 0 ? round(($marge / $recettes) * 100, 2) : null,
        ];
    }
}
