<?php

namespace App\Services;

use App\Enums\StatutCycleCulture;
use App\Enums\TypeIntervention;
use App\Events\RecolteEnregistree;
use App\Exceptions\RegleMetierException;
use App\Models\Culture;
use App\Models\CycleCulture;
use App\Models\Intervention;
use App\Models\Parcelle;
use App\Models\Scopes\ExploitationScope;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CycleCultureService
{
    public function __construct(private StockService $stockService) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function creer(array $data): CycleCulture
    {
        return DB::transaction(function () use ($data): CycleCulture {
            $parcelle = Parcelle::withoutGlobalScope(ExploitationScope::class)
                ->whereKey($data['parcelle_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $culture = Culture::withoutGlobalScope(ExploitationScope::class)
                ->where('exploitation_id', $parcelle->exploitation_id)
                ->whereKey($data['culture_id'])
                ->firstOrFail();

            if ((float) $data['surface_ha'] > (float) $parcelle->superficie_ha) {
                throw new RegleMetierException(
                    "La surface cultivée ({$data['surface_ha']} ha) dépasse la surface de la parcelle {$parcelle->code} ({$parcelle->superficie_ha} ha)."
                );
            }

            $cycleActif = CycleCulture::withoutGlobalScope(ExploitationScope::class)
                ->where('parcelle_id', $parcelle->getKey())
                ->whereIn('statut', [
                    StatutCycleCulture::Planifie->value,
                    StatutCycleCulture::EnCours->value,
                ])
                ->exists();

            if ($cycleActif) {
                throw new RegleMetierException(
                    "La parcelle {$parcelle->code} porte déjà un cycle cultural actif."
                );
            }

            return CycleCulture::withoutGlobalScope(ExploitationScope::class)->create([
                'exploitation_id' => $parcelle->exploitation_id,
                'parcelle_id' => $parcelle->getKey(),
                'culture_id' => $culture->getKey(),
                'campagne' => $data['campagne'],
                'date_semis' => $data['date_semis'],
                'date_recolte_prevue' => $data['date_recolte_prevue'] ?? null,
                'statut' => StatutCycleCulture::EnCours,
                'surface_ha' => $data['surface_ha'],
                'notes' => $data['notes'] ?? null,
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function enregistrerRecolte(CycleCulture $cycle, array $data, User $operateur): Intervention
    {
        return DB::transaction(function () use ($cycle, $data, $operateur): Intervention {
            $verrouille = CycleCulture::withoutGlobalScope(ExploitationScope::class)
                ->whereKey($cycle->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($verrouille->estRecolte()) {
                throw new RegleMetierException('Ce cycle cultural a déjà été récolté.');
            }

            $quantite = (float) $data['quantite_kg'];

            if ($quantite <= 0) {
                throw new RegleMetierException('La quantité récoltée doit être strictement positive.');
            }

            $dateRecolte = $data['date_recolte'] ?? now()->toDateString();
            $surface = (float) $verrouille->surface_ha;
            $rendement = $surface > 0 ? round(($quantite / 1000) / $surface, 2) : null;

            $intervention = Intervention::withoutGlobalScope(ExploitationScope::class)->create([
                'exploitation_id' => $verrouille->exploitation_id,
                'parcelle_id' => $verrouille->parcelle_id,
                'cycle_culture_id' => $verrouille->getKey(),
                'operateur_id' => $operateur->getKey(),
                'type' => TypeIntervention::Recolte,
                'date_intervention' => $dateRecolte,
                'quantite' => $quantite,
                'unite' => 'kg',
                'description' => $data['description'] ?? null,
            ]);

            $verrouille->forceFill([
                'statut' => StatutCycleCulture::Recolte,
                'date_recolte_reelle' => $dateRecolte,
                'quantite_recoltee_kg' => $quantite,
                'rendement_obtenu_tha' => $rendement,
            ])->save();

            $codeLot = sprintf(
                'LOT-%s-%s',
                $dateRecolte,
                Str::upper(Str::substr((string) Str::ulid(), -8)),
            );

            $this->stockService->entrer(
                exploitationId: $verrouille->exploitation_id,
                codeLot: $codeLot,
                quantite: $quantite,
                unite: 'kg',
                attributes: [
                    'parcelle_id' => $verrouille->parcelle_id,
                    'cycle_culture_id' => $verrouille->getKey(),
                    'intervention_id' => $intervention->getKey(),
                    'date_entree' => $dateRecolte,
                    'origine' => 'recolte',
                    'motif' => "Récolte {$verrouille->campagne} - parcelle {$verrouille->parcelle_id}",
                ],
                user: $operateur,
            );

            DB::afterCommit(fn () => RecolteEnregistree::dispatch($intervention, $verrouille));

            return $intervention;
        });
    }
}
