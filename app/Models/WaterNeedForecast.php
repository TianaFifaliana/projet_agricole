<?php

namespace App\Models;

use App\Enums\StatutPrevisionEau;
use App\Models\Concerns\BelongsToExploitation;
use App\Observers\WaterNeedForecastObserver;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

#[ObservedBy(WaterNeedForecastObserver::class)]
#[Fillable([
    'exploitation_id',
    'campaign_id',
    'campaign_water_budget_id',
    'surface_ha',
    'duree_cycle_jours',
    'date_semis',
    'rendement_reseau_pct',
    'marge_securite_pct',
    'besoin_net_total_m3',
    'besoin_brut_total_m3',
    'volume_propose_m3',
    'statut',
])]
class WaterNeedForecast extends Model
{
    use BelongsToExploitation, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'surface_ha' => 'decimal:2',
            'duree_cycle_jours' => 'integer',
            'date_semis' => 'date',
            'rendement_reseau_pct' => 'decimal:2',
            'marge_securite_pct' => 'decimal:2',
            'besoin_net_total_m3' => 'decimal:2',
            'besoin_brut_total_m3' => 'decimal:2',
            'volume_propose_m3' => 'decimal:2',
            'statut' => StatutPrevisionEau::class,
        ];
    }

    public function exploitation(): BelongsTo
    {
        return $this->belongsTo(Exploitation::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(CycleCulture::class, 'campaign_id');
    }

    public function budget(): BelongsTo
    {
        return $this->belongsTo(CampaignWaterBudget::class, 'campaign_water_budget_id');
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(WaterNeedForecastLine::class)->orderBy('date_debut');
    }

    public function estAllouee(): bool
    {
        return $this->statut === StatutPrevisionEau::Allouee;
    }

    /**
     * Volume d'eau théoriquement consommé à la date donnée, en suivant
     * la chronologie des phases culturales de la prévision.
     */
    public function consommationAttendue(?CarbonInterface $date = null): float
    {
        $reference = ($date ?? Carbon::today())->toDateTimeImmutable()->setTime(0, 0);
        $depart = $this->date_semis->toDateTimeImmutable()->setTime(0, 0);

        if ($reference < $depart) {
            return 0.0;
        }

        $restant = min(
            (int) $depart->diff($reference)->days,
            (int) $this->duree_cycle_jours,
        );

        $total = 0.0;

        foreach ($this->lignes as $ligne) {
            if ($restant <= 0) {
                break;
            }

            $consomme = min($restant, (int) $ligne->duree_jours);
            $total += $consomme * ((float) $ligne->besoin_brut_m3 / (int) $ligne->duree_jours);
            $restant -= $consomme;
        }

        return round($total, 2);
    }

    /**
     * Écart entre la consommation réelle du budget et la consommation
     * théorique à date : positif = sur-consommation, négatif = sous-consommation.
     */
    public function ecartConsommation(?CarbonInterface $date = null): ?float
    {
        if ($this->budget === null) {
            return null;
        }

        return round((float) $this->budget->volume_consomme_m3 - $this->consommationAttendue($date), 2);
    }

    public function pourcentageCycleEcoule(?CarbonInterface $date = null): float
    {
        $reference = ($date ?? Carbon::today())->toDateTimeImmutable()->setTime(0, 0);
        $depart = $this->date_semis->toDateTimeImmutable()->setTime(0, 0);

        if ($reference <= $depart) {
            return 0.0;
        }

        $ecoule = min((int) $depart->diff($reference)->days, (int) $this->duree_cycle_jours);

        return round(($ecoule / (int) $this->duree_cycle_jours) * 100, 2);
    }
}
