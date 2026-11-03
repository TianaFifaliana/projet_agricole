<?php

namespace App\Services;

use App\Enums\StatutVente;
use App\Enums\TypeMouvementStock;
use App\Events\VenteAnnulee;
use App\Events\VenteValidee;
use App\Exceptions\RegleMetierException;
use App\Models\Exploitation;
use App\Models\LotStock;
use App\Models\MouvementStock;
use App\Models\Partenaire;
use App\Models\Scopes\ExploitationScope;
use App\Models\User;
use App\Models\Vente;
use Illuminate\Support\Facades\DB;

class VenteService
{
    public function __construct(private StockService $stockService) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function creer(array $data, User $user): Vente
    {
        $exploitationId = $user->exploitation_id ?? (int) ($data['exploitation_id'] ?? 0);

        if ($exploitationId === 0) {
            throw new RegleMetierException('Une exploitation est requise pour créer une vente.');
        }

        return DB::transaction(function () use ($data, $user, $exploitationId): Vente {
            Exploitation::withoutGlobalScopes()
                ->whereKey($exploitationId)
                ->lockForUpdate()
                ->firstOrFail();

            Partenaire::withoutGlobalScope(ExploitationScope::class)
                ->where('exploitation_id', $exploitationId)
                ->whereKey($data['partenaire_id'])
                ->firstOrFail();

            $numero = $this->genererNumero($exploitationId, $data['date_vente']);

            $vente = Vente::withoutGlobalScope(ExploitationScope::class)->create([
                'exploitation_id' => $exploitationId,
                'partenaire_id' => $data['partenaire_id'],
                'user_id' => $user->getKey(),
                'numero' => $numero,
                'date_vente' => $data['date_vente'],
                'statut' => StatutVente::Brouillon,
                'total_ht' => 0,
                'tva' => 0,
                'total_ttc' => 0,
                'notes' => $data['notes'] ?? null,
            ]);

            $totalHt = 0.0;

            foreach ($data['lignes'] as $ligne) {
                $lotId = $ligne['lot_stock_id'] ?? null;

                if ($lotId !== null) {
                    $lot = LotStock::withoutGlobalScope(ExploitationScope::class)
                        ->where('exploitation_id', $exploitationId)
                        ->whereKey($lotId)
                        ->firstOrFail();
                }

                $montant = round((float) $ligne['quantite'] * (float) $ligne['prix_unitaire'], 2);
                $totalHt += $montant;

                $vente->lignes()->create([
                    'lot_stock_id' => $lotId,
                    'designation' => $ligne['designation'],
                    'quantite' => $ligne['quantite'],
                    'unite' => $ligne['unite'],
                    'prix_unitaire' => $ligne['prix_unitaire'],
                    'montant' => $montant,
                ]);
            }

            $tauxTva = (float) ($data['taux_tva'] ?? 0);
            $montantTva = round($totalHt * ($tauxTva / 100), 2);

            $vente->forceFill([
                'total_ht' => $totalHt,
                'tva' => $montantTva,
                'total_ttc' => round($totalHt + $montantTva, 2),
            ])->save();

            return $vente->refresh()->load('lignes');
        });
    }

    public function valider(Vente $vente, User $user): Vente
    {
        return DB::transaction(function () use ($vente, $user): Vente {
            $verrouillee = Vente::withoutGlobalScope(ExploitationScope::class)
                ->whereKey($vente->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $verrouillee->estBrouillon()) {
                throw new RegleMetierException('Seules les ventes au statut brouillon peuvent être validées.');
            }

            $lignes = $verrouillee->lignes()
                ->orderBy('lot_stock_id')
                ->get();

            if ($lignes->isEmpty()) {
                throw new RegleMetierException('Une vente doit contenir au moins une ligne.');
            }

            foreach ($lignes as $ligne) {
                if ($ligne->lot_stock_id === null) {
                    continue;
                }

                $lot = LotStock::withoutGlobalScope(ExploitationScope::class)
                    ->where('exploitation_id', $verrouillee->exploitation_id)
                    ->whereKey($ligne->lot_stock_id)
                    ->firstOrFail();

                $this->stockService->sortir(
                    lot: $lot,
                    quantite: (float) $ligne->quantite,
                    motif: "Validation de la vente {$verrouillee->numero}",
                    vente: $verrouillee,
                    user: $user,
                );
            }

            $verrouillee->forceFill([
                'statut' => StatutVente::Validee,
                'validee_at' => now(),
            ])->save();

            DB::afterCommit(fn () => VenteValidee::dispatch($verrouillee));

            return $verrouillee->refresh()->load('lignes', 'partenaire');
        });
    }

    public function annuler(Vente $vente, User $user): Vente
    {
        return DB::transaction(function () use ($vente, $user): Vente {
            $verrouillee = Vente::withoutGlobalScope(ExploitationScope::class)
                ->whereKey($vente->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($verrouillee->statut === StatutVente::Annulee) {
                return $verrouillee;
            }

            if ($verrouillee->statut === StatutVente::Validee) {
                $sorties = MouvementStock::withoutGlobalScope(ExploitationScope::class)
                    ->where('vente_id', $verrouillee->getKey())
                    ->where('type', TypeMouvementStock::Sortie->value)
                    ->orderBy('lot_stock_id')
                    ->get();

                foreach ($sorties as $sortie) {
                    $lot = LotStock::withoutGlobalScope(ExploitationScope::class)
                        ->where('exploitation_id', $verrouillee->exploitation_id)
                        ->whereKey($sortie->lot_stock_id)
                        ->firstOrFail();

                    $this->stockService->reintegrer(
                        lot: $lot,
                        quantite: (float) $sortie->quantite,
                        motif: "Annulation de la vente {$verrouillee->numero}",
                        vente: $verrouillee,
                        user: $user,
                    );
                }
            }

            $verrouillee->forceFill([
                'statut' => StatutVente::Annulee,
            ])->save();

            DB::afterCommit(fn () => VenteAnnulee::dispatch($verrouillee));

            return $verrouillee->refresh();
        });
    }

    private function genererNumero(int $exploitationId, string $dateVente): string
    {
        $annee = substr($dateVente, 0, 4);
        $prefixe = "VTE-{$annee}-";

        $dernierNumero = Vente::withoutGlobalScope(ExploitationScope::class)
            ->where('exploitation_id', $exploitationId)
            ->where('numero', 'like', $prefixe.'%')
            ->orderByDesc('numero')
            ->value('numero');

        $sequence = $dernierNumero === null ? 1 : ((int) substr($dernierNumero, -4)) + 1;

        do {
            $numero = $prefixe.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
            $existe = Vente::withoutGlobalScope(ExploitationScope::class)
                ->where('exploitation_id', $exploitationId)
                ->where('numero', $numero)
                ->exists();

            $sequence++;
        } while ($existe);

        return $numero;
    }
}
