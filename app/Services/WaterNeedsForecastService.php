<?php

namespace App\Services;

use App\Enums\PhaseCulturale;
use App\Enums\StatutCycleCulture;
use App\Enums\StatutPrevisionEau;
use App\Exceptions\RegleMetierException;
use App\Models\CampaignWaterBudget;
use App\Models\CycleCulture;
use App\Models\Scopes\ExploitationScope;
use App\Models\WaterNeedForecast;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WaterNeedsForecastService
{
    /**
     * Conversion agronomique : 1 mm d'eau épandue sur 1 hectare = 10 m³.
     */
    private const M3_PAR_HA_MM = 10.0;

    /**
     * Nombre minimal de jours attribués à chaque phase culturale.
     */
    private const JOURS_MINI_PAR_PHASE = 1;

    public function __construct(private WaterBudgetService $waterBudgets) {}

    /**
     * Établit la prévision de besoin en eau d'une campagne : répartition du
     * cycle variétal en phases culturales, besoins nets (mm/jour), besoins
     * bruts (pertes du réseau d'irrigation) et volume proposé (marge incluse).
     */
    public function generer(
        CycleCulture $campaign,
        ?float $rendementReseauPct = null,
        ?float $margeSecuritePct = null,
    ): WaterNeedForecast {
        $rendementReseauPct ??= (float) config('irrigation.rendement_reseau_defaut_pct', 70);
        $margeSecuritePct ??= (float) config('irrigation.marge_securite_defaut_pct', 10);

        $this->validerParametres($rendementReseauPct, $margeSecuritePct);

        return DB::transaction(function () use ($campaign, $rendementReseauPct, $margeSecuritePct): WaterNeedForecast {
            $verrouille = CycleCulture::withoutGlobalScope(ExploitationScope::class)
                ->whereKey($campaign->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($verrouille->statut !== StatutCycleCulture::EnCours) {
                throw new RegleMetierException('La prévision ne peut être établie que sur une campagne active.');
            }

            $existante = WaterNeedForecast::withoutGlobalScope(ExploitationScope::class)
                ->where('campaign_id', $verrouille->getKey())
                ->exists();

            if ($existante) {
                throw new RegleMetierException('Une prévision de besoin en eau existe déjà pour cette campagne.');
            }

            if ((float) $verrouille->surface_ha <= 0) {
                throw new RegleMetierException('La surface cultivée doit être strictement positive pour établir une prévision.');
            }

            $dureeCycle = (int) ($verrouille->culture?->duree_cycle_jours ?? 0);

            if ($dureeCycle < self::JOURS_MINI_PAR_PHASE * count(PhaseCulturale::cases())) {
                throw new RegleMetierException('Le cycle de la culture est trop court pour être réparti en phases culturales.');
            }

            $phases = $this->calculerPhases($verrouille, (float) $verrouille->surface_ha, $dureeCycle, $rendementReseauPct);

            $besoinNetTotal = round(array_sum(array_column($phases, 'besoin_net_m3')), 2);
            $besoinBrutTotal = round(array_sum(array_column($phases, 'besoin_brut_m3')), 2);

            $forecast = WaterNeedForecast::withoutGlobalScope(ExploitationScope::class)->create([
                'exploitation_id' => $verrouille->exploitation_id,
                'campaign_id' => $verrouille->getKey(),
                'surface_ha' => $verrouille->surface_ha,
                'duree_cycle_jours' => $dureeCycle,
                'date_semis' => $verrouille->date_semis,
                'rendement_reseau_pct' => $rendementReseauPct,
                'marge_securite_pct' => $margeSecuritePct,
                'besoin_net_total_m3' => $besoinNetTotal,
                'besoin_brut_total_m3' => $besoinBrutTotal,
                'volume_propose_m3' => round($besoinBrutTotal * (1 + $margeSecuritePct / 100), 2),
                'statut' => StatutPrevisionEau::Proposee,
            ]);

            $forecast->lignes()->createMany($phases);

            Log::channel('audit')->info('prevision_eau.generee', [
                'forecast_id' => $forecast->getKey(),
                'exploitation_id' => $forecast->exploitation_id,
                'campaign_id' => $forecast->campaign_id,
                'surface_ha' => (float) $forecast->surface_ha,
                'duree_cycle_jours' => $dureeCycle,
                'besoin_net_total_m3' => $besoinNetTotal,
                'besoin_brut_total_m3' => $besoinBrutTotal,
                'volume_propose_m3' => (float) $forecast->volume_propose_m3,
                'rendement_reseau_pct' => $rendementReseauPct,
                'marge_securite_pct' => $margeSecuritePct,
            ]);

            return $forecast->load('lignes');
        });
    }

    /**
     * Établit la prévision (ou réutilise une proposition en attente) puis
     * alloue automatiquement le budget d'eau de la campagne avec le volume
     * proposé.
     */
    public function proposerEtAllouer(
        CycleCulture $campaign,
        ?float $seuilAlertePct = null,
        ?float $rendementReseauPct = null,
        ?float $margeSecuritePct = null,
    ): CampaignWaterBudget {
        return DB::transaction(function () use ($campaign, $seuilAlertePct, $rendementReseauPct, $margeSecuritePct): CampaignWaterBudget {
            $existante = WaterNeedForecast::withoutGlobalScope(ExploitationScope::class)
                ->where('campaign_id', $campaign->getKey())
                ->lockForUpdate()
                ->first();

            if ($existante !== null && $existante->estAllouee()) {
                throw new RegleMetierException('Le budget de cette campagne a déjà été alloué à partir d\'une prévision.');
            }

            $forecast = $existante ?? $this->generer($campaign, $rendementReseauPct, $margeSecuritePct);

            $budget = $this->waterBudgets->allocateBudget($campaign, (float) $forecast->volume_propose_m3, $seuilAlertePct);

            $forecast->forceFill([
                'statut' => StatutPrevisionEau::Allouee,
                'campaign_water_budget_id' => $budget->getKey(),
            ])->save();

            Log::channel('audit')->info('prevision_eau.allouee', [
                'forecast_id' => $forecast->getKey(),
                'exploitation_id' => $forecast->exploitation_id,
                'campaign_id' => $forecast->campaign_id,
                'budget_id' => $budget->getKey(),
                'volume_alloue_m3' => (float) $budget->volume_alloue_m3,
                'volume_propose_m3' => (float) $forecast->volume_propose_m3,
            ]);

            return $budget;
        });
    }

    public function previsionPour(CycleCulture $campaign): ?WaterNeedForecast
    {
        return WaterNeedForecast::withoutGlobalScope(ExploitationScope::class)
            ->where('campaign_id', $campaign->getKey())
            ->first();
    }

    /**
     * Répartit la durée du cycle variétal en phases culturales et calcule
     * pour chacune ses dates, son besoin net (surface × durée × besoin
     * journalier × 10 m³ par ha·mm) et son besoin brut majoré des pertes
     * du réseau d'irrigation.
     *
     * @return array<int, array{phase: PhaseCulturale, date_debut: string, date_fin: string, duree_jours: int, besoin_journalier_mm: float, besoin_net_m3: float, besoin_brut_m3: float}>
     */
    private function calculerPhases(CycleCulture $campaign, float $surfaceHa, int $dureeCycle, float $rendementReseauPct): array
    {
        $repartition = [];

        foreach (PhaseCulturale::cases() as $phase) {
            $parametres = $phase->parametres();

            if ($parametres['besoin_journalier_mm'] <= 0) {
                throw new RegleMetierException("La phase culturale « {$phase->value} » n'est pas configurée.");
            }

            $repartition[] = ['phase' => $phase] + $parametres;
        }

        $durees = [];
        $attribues = 0;

        foreach (array_slice($repartition, 0, -1) as $repart) {
            $duree = max(self::JOURS_MINI_PAR_PHASE, (int) round($dureeCycle * $repart['part_cycle']));
            $durees[] = $duree;
            $attribues += $duree;
        }

        $reste = $dureeCycle - $attribues;

        if ($reste < self::JOURS_MINI_PAR_PHASE) {
            throw new RegleMetierException('La répartition des phases culturales est incohérente avec la durée du cycle.');
        }

        $durees[] = $reste;

        $phases = [];
        $dateDebut = $campaign->date_semis->copy();

        foreach ($repartition as $index => $repart) {
            $duree = $durees[$index];
            $besoinNet = round($surfaceHa * $duree * $repart['besoin_journalier_mm'] * self::M3_PAR_HA_MM, 2);

            $phases[] = [
                'phase' => $repart['phase'],
                'date_debut' => $dateDebut->toDateString(),
                'date_fin' => $dateDebut->copy()->addDays($duree - 1)->toDateString(),
                'duree_jours' => $duree,
                'besoin_journalier_mm' => $repart['besoin_journalier_mm'],
                'besoin_net_m3' => $besoinNet,
                'besoin_brut_m3' => round($besoinNet / ($rendementReseauPct / 100), 2),
            ];

            $dateDebut->addDays($duree);
        }

        return $phases;
    }

    private function validerParametres(float $rendementReseauPct, float $margeSecuritePct): void
    {
        if ($rendementReseauPct <= 0 || $rendementReseauPct > 100) {
            throw new RegleMetierException('Le rendement du réseau d\'irrigation doit être compris entre 1 et 100 %.');
        }

        if ($margeSecuritePct < 0 || $margeSecuritePct > 100) {
            throw new RegleMetierException('La marge de sécurité doit être comprise entre 0 et 100 %.');
        }
    }
}
