<?php

namespace App\Services;

use App\Enums\StatutLotStock;
use App\Enums\TypeMouvementStock;
use App\Events\StockSousSeuil;
use App\Exceptions\RegleMetierException;
use App\Exceptions\StockInsuffisantException;
use App\Models\Intervention;
use App\Models\Intrant;
use App\Models\LotStock;
use App\Models\MouvementStock;
use App\Models\Scopes\ExploitationScope;
use App\Models\User;
use App\Models\Vente;
use Illuminate\Support\Facades\DB;

class StockService
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function entrer(
        int $exploitationId,
        string $codeLot,
        float $quantite,
        string $unite,
        array $attributes = [],
        ?User $user = null,
    ): LotStock {
        if ($quantite <= 0) {
            throw new RegleMetierException('La quantité entrante doit être strictement positive.');
        }

        return DB::transaction(function () use ($exploitationId, $codeLot, $quantite, $unite, $attributes, $user): LotStock {
            $lot = LotStock::withoutGlobalScope(ExploitationScope::class)->create([
                'exploitation_id' => $exploitationId,
                'intrant_id' => $attributes['intrant_id'] ?? null,
                'parcelle_id' => $attributes['parcelle_id'] ?? null,
                'cycle_culture_id' => $attributes['cycle_culture_id'] ?? null,
                'intervention_id' => $attributes['intervention_id'] ?? null,
                'code_lot' => $codeLot,
                'quantite_initiale' => $quantite,
                'quantite_restante' => $quantite,
                'unite' => $unite,
                'date_entree' => $attributes['date_entree'] ?? now()->toDateString(),
                'date_peremption' => $attributes['date_peremption'] ?? null,
                'origine' => $attributes['origine'] ?? null,
                'prix_unitaire' => $attributes['prix_unitaire'] ?? null,
                'statut' => StatutLotStock::Disponible,
            ]);

            $this->enregistrerMouvement(
                lot: $lot,
                type: TypeMouvementStock::Entree,
                quantite: $quantite,
                avant: 0,
                apres: $quantite,
                motif: $attributes['motif'] ?? 'Entrée de stock',
                user: $user,
                intervention: ($attributes['intervention_id'] ?? null) !== null
                    ? Intervention::withoutGlobalScope(ExploitationScope::class)->find($attributes['intervention_id'])
                    : null,
            );

            return $lot->refresh();
        });
    }

    public function sortir(
        LotStock $lot,
        float $quantite,
        string $motif,
        ?Vente $vente = null,
        ?Intervention $intervention = null,
        ?User $user = null,
    ): MouvementStock {
        if ($quantite <= 0) {
            throw new RegleMetierException('La quantité sortante doit être strictement positive.');
        }

        return DB::transaction(function () use ($lot, $quantite, $motif, $vente, $intervention, $user): MouvementStock {
            $verrouille = LotStock::withoutGlobalScope(ExploitationScope::class)
                ->whereKey($lot->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($verrouille->statut !== StatutLotStock::Disponible) {
                throw new RegleMetierException("Le lot {$verrouille->code_lot} n'est pas disponible à la sortie.");
            }

            $avant = (float) $verrouille->quantite_restante;

            if ($avant + 0.0001 < $quantite) {
                throw StockInsuffisantException::pour($verrouille, $quantite);
            }

            $apres = round($avant - $quantite, 2);

            $verrouille->forceFill([
                'quantite_restante' => $apres,
                'statut' => $apres <= 0 ? StatutLotStock::Epuise : StatutLotStock::Disponible,
            ])->save();

            $mouvement = $this->enregistrerMouvement(
                lot: $verrouille,
                type: TypeMouvementStock::Sortie,
                quantite: $quantite,
                avant: $avant,
                apres: $apres,
                motif: $motif,
                user: $user,
                vente: $vente,
                intervention: $intervention,
            );

            DB::afterCommit(fn () => $this->verifierSeuil($verrouille));

            return $mouvement;
        });
    }

    public function reintegrer(
        LotStock $lot,
        float $quantite,
        string $motif,
        ?Vente $vente = null,
        ?User $user = null,
    ): MouvementStock {
        if ($quantite <= 0) {
            throw new RegleMetierException('La quantité à réintégrer doit être strictement positive.');
        }

        return DB::transaction(function () use ($lot, $quantite, $motif, $vente, $user): MouvementStock {
            $verrouille = LotStock::withoutGlobalScope(ExploitationScope::class)
                ->whereKey($lot->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $avant = (float) $verrouille->quantite_restante;
            $apres = round($avant + $quantite, 2);

            $verrouille->forceFill([
                'quantite_restante' => $apres,
                'statut' => StatutLotStock::Disponible,
            ])->save();

            return $this->enregistrerMouvement(
                lot: $verrouille,
                type: TypeMouvementStock::Ajustement,
                quantite: $quantite,
                avant: $avant,
                apres: $apres,
                motif: $motif,
                user: $user,
                vente: $vente,
            );
        });
    }

    private function enregistrerMouvement(
        LotStock $lot,
        TypeMouvementStock $type,
        float $quantite,
        float $avant,
        float $apres,
        string $motif,
        ?User $user = null,
        ?Vente $vente = null,
        ?Intervention $intervention = null,
        ?string $reference = null,
    ): MouvementStock {
        return MouvementStock::withoutGlobalScope(ExploitationScope::class)->create([
            'exploitation_id' => $lot->exploitation_id,
            'lot_stock_id' => $lot->getKey(),
            'vente_id' => $vente?->getKey(),
            'intervention_id' => $intervention?->getKey(),
            'user_id' => $user?->getKey(),
            'type' => $type,
            'quantite' => $quantite,
            'quantite_avant' => $avant,
            'quantite_apres' => $apres,
            'motif' => $motif,
            'reference' => $reference,
            'date_mouvement' => now(),
        ]);
    }

    private function verifierSeuil(LotStock $lot): void
    {
        if ($lot->intrant_id === null) {
            return;
        }

        $intrant = Intrant::withoutGlobalScope(ExploitationScope::class)->find($lot->intrant_id);

        if (! $intrant instanceof Intrant || (float) $intrant->seuil_alerte <= 0) {
            return;
        }

        $disponible = (float) LotStock::withoutGlobalScope(ExploitationScope::class)
            ->where('intrant_id', $intrant->getKey())
            ->where('statut', StatutLotStock::Disponible->value)
            ->sum('quantite_restante');

        if ($disponible <= (float) $intrant->seuil_alerte) {
            StockSousSeuil::dispatch($intrant, $disponible);
        }
    }
}
