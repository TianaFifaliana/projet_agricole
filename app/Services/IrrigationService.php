<?php

namespace App\Services;

use App\Enums\StatutBudgetEau;
use App\Enums\StatutCycleCulture;
use App\Exceptions\RegleMetierException;
use App\Exceptions\WaterQuotaExceededException;
use App\Models\CampaignWaterBudget;
use App\Models\CycleCulture;
use App\Models\Irrigation;
use App\Models\Parcelle;
use App\Models\Scopes\ExploitationScope;
use App\Models\User;
use App\Models\WaterSource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class IrrigationService
{
    public function __construct(
        private WaterBudgetService $waterBudgets,
        private FinanceService $finance,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function recordIrrigation(array $data, User $user): Irrigation
    {
        return DB::transaction(function () use ($data, $user): Irrigation {
            $campaign = CycleCulture::withoutGlobalScope(ExploitationScope::class)
                ->whereKey($data['campaign_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if ($campaign->statut !== StatutCycleCulture::EnCours) {
                throw new RegleMetierException('La campagne est terminée : aucune irrigation ne peut être enregistrée.');
            }

            if (! $user->isAdministrateur() && (int) $user->exploitation_id !== (int) $campaign->exploitation_id) {
                throw new RegleMetierException('Vous ne pouvez pas irriguer une campagne d\'une autre exploitation.');
            }

            $parcelle = Parcelle::withoutGlobalScope(ExploitationScope::class)
                ->where('exploitation_id', $campaign->exploitation_id)
                ->whereKey($data['production_unit_id'])
                ->firstOrFail();

            $source = WaterSource::withoutGlobalScope(ExploitationScope::class)
                ->where('exploitation_id', $campaign->exploitation_id)
                ->whereKey($data['water_source_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if (! $source->estOperationnelle()) {
                throw new RegleMetierException("La source d'eau « {$source->nom} » n'est pas opérationnelle.");
            }

            $debit = (float) ($data['debit_m3_h'] ?? $source->debit_m3_h);

            if ($debit <= 0) {
                throw new RegleMetierException('Le débit doit être strictement positif.');
            }

            if ($debit > (float) $source->debit_m3_h + 0.0001) {
                throw new RegleMetierException("Le débit demandé dépasse la capacité de la source « {$source->nom} ».");
            }

            $dureeMinutes = (int) $data['duree_minutes'];
            $volume = $this->calculerVolume($debit, $dureeMinutes);

            $capacite = (float) $parcelle->superficie_ha * (float) config('irrigation.capacite_m3_par_ha', 5000);

            if ($volume > $capacite + 0.0001) {
                throw new RegleMetierException(sprintf(
                    'Le volume calculé (%s m³) dépasse la capacité de la rizière %s (%s m³).',
                    number_format($volume, 2, ',', ' '),
                    $parcelle->code,
                    number_format($capacite, 2, ',', ' '),
                ));
            }

            $budget = CampaignWaterBudget::withoutGlobalScope(ExploitationScope::class)
                ->where('campaign_id', $campaign->getKey())
                ->lockForUpdate()
                ->first();

            if (! $budget instanceof CampaignWaterBudget) {
                throw new RegleMetierException('Aucun budget d\'eau n\'est alloué pour cette campagne.');
            }

            if ($budget->statut === StatutBudgetEau::Cloture) {
                throw new RegleMetierException('Ce budget d\'eau est clôturé.');
            }

            $volumeRestant = (float) $budget->volume_alloue_m3 - (float) $budget->volume_consomme_m3;

            if ($volume > $volumeRestant + 0.0001) {
                throw WaterQuotaExceededException::pour($budget, $volume);
            }

            $irrigation = Irrigation::withoutGlobalScope(ExploitationScope::class)->create([
                'exploitation_id' => $campaign->exploitation_id,
                'campaign_id' => $campaign->getKey(),
                'production_unit_id' => $parcelle->getKey(),
                'water_source_id' => $source->getKey(),
                'user_id' => $user->getKey(),
                'date' => $data['date'],
                'duree_minutes' => $dureeMinutes,
                'debit_m3_h' => $debit,
                'volume_m3' => $volume,
                'cout' => $data['cout'] ?? null,
                'observation' => $data['observation'] ?? null,
            ]);

            $budget->forceFill([
                'volume_consomme_m3' => round((float) $budget->volume_consomme_m3 + $volume, 2),
            ])->save();

            $this->waterBudgets->actualiserStatut($budget);

            $depense = $this->finance->enregistrerDepenseIrrigation($irrigation, $volume, $dureeMinutes, $user);

            Log::channel('audit')->info('irrigation.enregistree', [
                'irrigation_id' => $irrigation->getKey(),
                'exploitation_id' => $irrigation->exploitation_id,
                'campaign_id' => $irrigation->campaign_id,
                'production_unit_id' => $irrigation->production_unit_id,
                'water_source_id' => $irrigation->water_source_id,
                'user_id' => $user->getKey(),
                'volume_m3' => $volume,
                'duree_minutes' => $dureeMinutes,
                'debit_m3_h' => $debit,
                'volume_alloue_m3' => (float) $budget->volume_alloue_m3,
                'volume_consomme_m3' => (float) $budget->volume_consomme_m3,
                'depense_id' => $depense?->getKey(),
                'depense_montant' => $depense?->montant,
            ]);

            return $irrigation->refresh();
        });
    }

    public function calculerVolume(float $debit, int $dureeMinutes): float
    {
        return round($debit * ($dureeMinutes / 60), 2);
    }
}
