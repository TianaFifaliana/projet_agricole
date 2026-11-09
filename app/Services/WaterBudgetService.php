<?php

namespace App\Services;

use App\Enums\StatutBudgetEau;
use App\Enums\StatutCycleCulture;
use App\Enums\StatutRallonge;
use App\Events\WaterQuotaCritical;
use App\Events\WaterQuotaExhausted;
use App\Exceptions\RegleMetierException;
use App\Exceptions\WaterQuotaExceededException;
use App\Models\CampaignWaterBudget;
use App\Models\CycleCulture;
use App\Models\Scopes\ExploitationScope;
use App\Models\User;
use App\Models\WaterBudgetAdjustment;
use Illuminate\Support\Facades\DB;

class WaterBudgetService
{
    public function allocateBudget(
        CycleCulture $campaign,
        float $volume,
        ?float $seuilAlertePct = null,
    ): CampaignWaterBudget {
        if ($volume <= 0) {
            throw new RegleMetierException('Le volume alloué doit être strictement positif.');
        }

        return DB::transaction(function () use ($campaign, $volume, $seuilAlertePct): CampaignWaterBudget {
            $verrouille = CycleCulture::withoutGlobalScope(ExploitationScope::class)
                ->whereKey($campaign->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($verrouille->statut !== StatutCycleCulture::EnCours) {
                throw new RegleMetierException('Le budget ne peut être alloué que sur une campagne active.');
            }

            $existe = CampaignWaterBudget::withoutGlobalScope(ExploitationScope::class)
                ->where('campaign_id', $verrouille->getKey())
                ->exists();

            if ($existe) {
                throw new RegleMetierException('Un budget d\'eau est déjà alloué pour cette campagne.');
            }

            return CampaignWaterBudget::withoutGlobalScope(ExploitationScope::class)->create([
                'exploitation_id' => $verrouille->exploitation_id,
                'campaign_id' => $verrouille->getKey(),
                'volume_alloue_m3' => round($volume, 2),
                'volume_consomme_m3' => 0,
                'seuil_alerte_pct' => $seuilAlertePct ?? (float) config('irrigation.seuil_alerte_defaut_pct', 80),
                'statut' => StatutBudgetEau::Actif,
            ]);
        });
    }

    public function budgetFor(CycleCulture $campaign): ?CampaignWaterBudget
    {
        return CampaignWaterBudget::withoutGlobalScope(ExploitationScope::class)
            ->where('campaign_id', $campaign->getKey())
            ->first();
    }

    public function getRemainingVolume(CycleCulture|CampaignWaterBudget $campaign): float
    {
        $budget = $campaign instanceof CampaignWaterBudget
            ? $campaign
            : $this->budgetFor($campaign);

        return $budget?->volumeRestant() ?? 0.0;
    }

    public function consume(CampaignWaterBudget $budget, float $volume): CampaignWaterBudget
    {
        if ($volume <= 0) {
            throw new RegleMetierException('Le volume consommé doit être strictement positif.');
        }

        return DB::transaction(function () use ($budget, $volume): CampaignWaterBudget {
            $verrouille = CampaignWaterBudget::withoutGlobalScope(ExploitationScope::class)
                ->whereKey($budget->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($verrouille->statut === StatutBudgetEau::Cloture) {
                throw new RegleMetierException('Ce budget d\'eau est clôturé.');
            }

            if ($volume > $verrouille->volumeRestant() + 0.0001) {
                throw WaterQuotaExceededException::pour($verrouille, $volume);
            }

            $verrouille->forceFill([
                'volume_consomme_m3' => round((float) $verrouille->volume_consomme_m3 + $volume, 2),
            ])->save();

            $this->actualiserStatut($verrouille);

            return $verrouille->refresh();
        });
    }

    public function requestAdjustment(
        CampaignWaterBudget|CycleCulture $budget,
        float $volume,
        string $motif,
        User $demandeur,
    ): WaterBudgetAdjustment {
        $cible = $budget instanceof CampaignWaterBudget ? $budget : $this->budgetFor($budget);

        if (! $cible instanceof CampaignWaterBudget) {
            throw new RegleMetierException('Aucun budget d\'eau n\'est alloué pour cette campagne.');
        }

        if ($volume <= 0) {
            throw new RegleMetierException('Le volume supplémentaire doit être strictement positif.');
        }

        if (trim($motif) === '') {
            throw new RegleMetierException('Le motif de la rallonge est obligatoire.');
        }

        return DB::transaction(function () use ($cible, $volume, $motif, $demandeur): WaterBudgetAdjustment {
            $verrouille = CampaignWaterBudget::withoutGlobalScope(ExploitationScope::class)
                ->whereKey($cible->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $enAttente = WaterBudgetAdjustment::withoutGlobalScope(ExploitationScope::class)
                ->where('campaign_water_budget_id', $verrouille->getKey())
                ->where('statut', StatutRallonge::EnAttente->value)
                ->exists();

            if ($enAttente) {
                throw new RegleMetierException('Une demande de rallonge est déjà en attente pour ce budget.');
            }

            return WaterBudgetAdjustment::withoutGlobalScope(ExploitationScope::class)->create([
                'exploitation_id' => $verrouille->exploitation_id,
                'campaign_water_budget_id' => $verrouille->getKey(),
                'demande_par_id' => $demandeur->getKey(),
                'volume_supplementaire_m3' => round($volume, 2),
                'motif' => $motif,
                'statut' => StatutRallonge::EnAttente,
            ]);
        });
    }

    public function approveAdjustment(
        WaterBudgetAdjustment $adjustment,
        User $valideur,
        ?string $commentaire = null,
    ): WaterBudgetAdjustment {
        if (! $valideur->isResponsable() && ! $valideur->isAdministrateur()) {
            throw new RegleMetierException('Seul le responsable peut approuver une rallonge de quota.');
        }

        return DB::transaction(function () use ($adjustment, $valideur, $commentaire): WaterBudgetAdjustment {
            $verrouille = WaterBudgetAdjustment::withoutGlobalScope(ExploitationScope::class)
                ->whereKey($adjustment->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $verrouille->estEnAttente()) {
                throw new RegleMetierException('Cette demande de rallonge a déjà été traitée.');
            }

            $budget = CampaignWaterBudget::withoutGlobalScope(ExploitationScope::class)
                ->whereKey($verrouille->campaign_water_budget_id)
                ->lockForUpdate()
                ->firstOrFail();

            $budget->forceFill([
                'volume_alloue_m3' => round((float) $budget->volume_alloue_m3 + (float) $verrouille->volume_supplementaire_m3, 2),
            ])->save();

            $this->actualiserStatut($budget);

            $verrouille->forceFill([
                'statut' => StatutRallonge::Approuvee,
                'valide_par_id' => $valideur->getKey(),
                'date_decision' => now(),
                'commentaire_decision' => $commentaire,
            ])->save();

            return $verrouille->refresh();
        });
    }

    public function refuseAdjustment(
        WaterBudgetAdjustment $adjustment,
        User $valideur,
        ?string $commentaire = null,
    ): WaterBudgetAdjustment {
        if (! $valideur->isResponsable() && ! $valideur->isAdministrateur()) {
            throw new RegleMetierException('Seul le responsable peut refuser une rallonge de quota.');
        }

        return DB::transaction(function () use ($adjustment, $valideur, $commentaire): WaterBudgetAdjustment {
            $verrouille = WaterBudgetAdjustment::withoutGlobalScope(ExploitationScope::class)
                ->whereKey($adjustment->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $verrouille->estEnAttente()) {
                throw new RegleMetierException('Cette demande de rallonge a déjà été traitée.');
            }

            $verrouille->forceFill([
                'statut' => StatutRallonge::Refusee,
                'valide_par_id' => $valideur->getKey(),
                'date_decision' => now(),
                'commentaire_decision' => $commentaire,
            ])->save();

            return $verrouille->refresh();
        });
    }

    public function actualiserStatut(CampaignWaterBudget $budget): void
    {
        if ($budget->statut === StatutBudgetEau::Cloture) {
            return;
        }

        $ancienStatut = $budget->statut;

        $alloue = (float) $budget->volume_alloue_m3;
        $consomme = (float) $budget->volume_consomme_m3;
        $pourcentage = $alloue > 0 ? ($consomme / $alloue) * 100 : 100;

        $statut = match (true) {
            $consomme >= $alloue => StatutBudgetEau::Epuise,
            $pourcentage >= (float) $budget->seuil_alerte_pct => StatutBudgetEau::Alerte,
            default => StatutBudgetEau::Actif,
        };

        if ($ancienStatut === $statut) {
            return;
        }

        $budget->forceFill(['statut' => $statut])->save();

        if ($statut === StatutBudgetEau::Epuise) {
            WaterQuotaExhausted::dispatch($budget);

            return;
        }

        if ($statut === StatutBudgetEau::Alerte && $ancienStatut === StatutBudgetEau::Actif) {
            WaterQuotaCritical::dispatch($budget);
        }
    }
}
