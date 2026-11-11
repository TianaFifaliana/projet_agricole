<?php

namespace App\Services;

use App\Enums\PhaseCulturale;
use App\Enums\StatutCycleCulture;
use App\Enums\StatutReleveEau;
use App\Events\FuiteProbableDetectee;
use App\Exceptions\RegleMetierException;
use App\Models\CycleCulture;
use App\Models\Irrigation;
use App\Models\ReleveNiveauEau;
use App\Models\Scopes\ExploitationScope;
use App\Models\User;
use App\Models\WaterNeedForecast;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SuiviNiveauEauService
{
    /**
     * Conversion agronomique : 1 mm d'eau épandue sur 1 hectare = 10 m³.
     */
    private const M3_PAR_HA_MM = 10.0;

    /**
     * Nombre minimal de jours attribués à chaque phase culturale.
     */
    private const JOURS_MINI_PAR_PHASE = 1;

    /**
     * Enregistre un relevé de niveau d'eau dans la rizière et calcule le
     * bilan hydrique depuis le relevé précédent : apports (irrigations,
     * pluie), pertes attendues (évaporation selon la phase culturale,
     * percolation) puis écart prévu/réel. Un écart au-delà de la tolérance
     * signale une fuite probable et déclenche l'alerte correspondante.
     *
     * @param  array<string, mixed>  $data
     */
    public function enregistrer(array $data, User $user): ReleveNiveauEau
    {
        $releve = DB::transaction(function () use ($data, $user): ReleveNiveauEau {
            $campaign = CycleCulture::withoutGlobalScope(ExploitationScope::class)
                ->whereKey($data['campaign_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if ($campaign->statut !== StatutCycleCulture::EnCours) {
                throw new RegleMetierException('La campagne est terminée : aucun relevé de niveau d\'eau ne peut être enregistré.');
            }

            if (! $user->isAdministrateur() && (int) $user->exploitation_id !== (int) $campaign->exploitation_id) {
                throw new RegleMetierException('Vous ne pouvez pas relever le niveau d\'eau d\'une campagne d\'une autre exploitation.');
            }

            $dateReleve = Carbon::parse($data['date_releve'])->startOfDay();

            if ($dateReleve->lt($campaign->date_semis)) {
                throw new RegleMetierException('Le relevé ne peut pas être antérieur au semis de la campagne.');
            }

            if ($dateReleve->gt(Carbon::today())) {
                throw new RegleMetierException('Le relevé ne peut pas être postérieur à la date du jour.');
            }

            $niveauConstate = (float) $data['niveau_constate_mm'];
            $precipitations = (float) ($data['precipitations_mm'] ?? 0);

            if ($niveauConstate < 0 || $precipitations < 0) {
                throw new RegleMetierException('Le niveau d\'eau constaté et les précipitations ne peuvent pas être négatifs.');
            }

            $precedent = ReleveNiveauEau::withoutGlobalScope(ExploitationScope::class)
                ->where('exploitation_id', $campaign->exploitation_id)
                ->where('campaign_id', $campaign->getKey())
                ->where('parcelle_id', $campaign->parcelle_id)
                ->orderByDesc('date_releve')
                ->lockForUpdate()
                ->first();

            if ($precedent !== null && $dateReleve->isSameDay($precedent->date_releve)) {
                throw new RegleMetierException('Un relevé de niveau d\'eau existe déjà pour cette rizière à cette date.');
            }

            if ($precedent !== null && $dateReleve->lt($precedent->date_releve)) {
                throw new RegleMetierException(sprintf(
                    'Les relevés doivent être saisis dans l\'ordre chronologique : le dernier relevé date du %s.',
                    $precedent->date_releve->format('d/m/Y'),
                ));
            }

            $bilan = $this->calculerBilan($campaign, $precedent, $dateReleve, $niveauConstate, $precipitations);

            $releve = ReleveNiveauEau::withoutGlobalScope(ExploitationScope::class)->create([
                'exploitation_id' => $campaign->exploitation_id,
                'campaign_id' => $campaign->getKey(),
                'parcelle_id' => $campaign->parcelle_id,
                'user_id' => $user->getKey(),
                'date_releve' => $dateReleve->toDateString(),
                'niveau_constate_mm' => $niveauConstate,
                'precipitations_mm' => $precipitations,
                'observation' => $data['observation'] ?? null,
            ] + $bilan);

            Log::channel('audit')->info('releve_niveau_eau.enregistre', [
                'releve_id' => $releve->getKey(),
                'exploitation_id' => $releve->exploitation_id,
                'campaign_id' => $releve->campaign_id,
                'parcelle_id' => $releve->parcelle_id,
                'user_id' => $user->getKey(),
                'date_releve' => $releve->date_releve->toDateString(),
                'niveau_constate_mm' => (float) $releve->niveau_constate_mm,
                'niveau_attendu_mm' => $releve->niveau_attendu_mm === null ? null : (float) $releve->niveau_attendu_mm,
                'ecart_mm' => $releve->ecart_mm === null ? null : (float) $releve->ecart_mm,
                'pertes_anormales_m3' => $releve->pertes_anormales_m3 === null ? null : (float) $releve->pertes_anormales_m3,
                'statut' => $releve->statut->value,
            ]);

            return $releve;
        });

        if ($releve->statut === StatutReleveEau::FuiteProbable) {
            FuiteProbableDetectee::dispatch($releve);
        }

        return $releve;
    }

    /**
     * Historique des relevés d'une campagne, du plus ancien au plus récent :
     * suivi de l'évolution du niveau d'eau et des pertes constatées.
     *
     * @return Collection<int, ReleveNiveauEau>
     */
    public function evolution(CycleCulture $campaign): Collection
    {
        return ReleveNiveauEau::withoutGlobalScope(ExploitationScope::class)
            ->where('exploitation_id', $campaign->exploitation_id)
            ->where('campaign_id', $campaign->getKey())
            ->orderBy('date_releve')
            ->get();
    }

    /**
     * Confronte, à la date donnée, le besoin en eau prévu par la prévision
     * (chronologie des phases culturales) au volume réellement épandu par
     * les irrigations, et agrège les pertes relevées au champ : pertes
     * normales attendues (évaporation, percolation) et pertes anormales
     * correspondant aux fuites probables.
     *
     * @return array{prevu_m3: ?float, reel_m3: float, ecart_m3: ?float, ecart_pct: ?float, pertes_normales_m3: float, pertes_anormales_m3: float, dernier_niveau_mm: ?float}
     */
    public function comparaisonPrevuReel(CycleCulture $campaign, ?CarbonInterface $date = null): array
    {
        $reference = ($date ?? Carbon::today())->startOfDay();

        $releves = $this->evolution($campaign);

        $surfaceHa = (float) $campaign->surface_ha;

        $pertesNormalesMm = 0.0;

        foreach ($releves as $releve) {
            $pertesNormalesMm += $releve->pertesNormalesMm();
        }

        $pertesNormalesM3 = round($pertesNormalesMm * $surfaceHa * self::M3_PAR_HA_MM, 2);
        $pertesAnormalesM3 = round($releves->sum(fn (ReleveNiveauEau $releve): float => (float) ($releve->pertes_anormales_m3 ?? 0)), 2);

        $reelM3 = round((float) Irrigation::withoutGlobalScope(ExploitationScope::class)
            ->where('exploitation_id', $campaign->exploitation_id)
            ->where('campaign_id', $campaign->getKey())
            ->whereDate('date', '<=', $reference->toDateString())
            ->sum('volume_m3'), 2);

        $forecast = WaterNeedForecast::withoutGlobalScope(ExploitationScope::class)
            ->with('lignes')
            ->where('campaign_id', $campaign->getKey())
            ->first();

        $prevuM3 = $forecast?->consommationAttendue($reference);

        $ecartM3 = $prevuM3 !== null ? round($reelM3 - $prevuM3, 2) : null;
        $ecartPct = ($prevuM3 !== null && $prevuM3 > 0) ? round($ecartM3 / $prevuM3 * 100, 2) : null;

        $dernier = $releves->last();

        return [
            'prevu_m3' => $prevuM3,
            'reel_m3' => $reelM3,
            'ecart_m3' => $ecartM3,
            'ecart_pct' => $ecartPct,
            'pertes_normales_m3' => $pertesNormalesM3,
            'pertes_anormales_m3' => $pertesAnormalesM3,
            'dernier_niveau_mm' => $dernier?->niveau_constate_mm === null ? null : (float) $dernier->niveau_constate_mm,
        ];
    }

    /**
     * Calcule le bilan hydrique du relevé : apports (irrigations de
     * l'intervalle converties en lame d'eau, pluie), pertes attendues
     * (évaporation des phases traversées, percolation), niveau attendu,
     * écart prévu/réel et pertes anormales estimées. Le premier relevé
     * fixe la référence sans bilan.
     *
     * @param  array<string, mixed>  $bilan
     */
    private function calculerBilan(
        CycleCulture $campaign,
        ?ReleveNiveauEau $precedent,
        CarbonInterface $dateReleve,
        float $niveauConstate,
        float $precipitations,
    ): array {
        $phase = $this->phaseCulturale($campaign, $dateReleve);

        if ($precedent === null) {
            return [
                'phase_culturale' => $phase,
                'niveau_precedent_mm' => null,
                'apport_irrigation_m3' => 0.0,
                'evaporation_attendue_mm' => 0.0,
                'percolation_attendue_mm' => 0.0,
                'niveau_attendu_mm' => null,
                'ecart_mm' => null,
                'pertes_anormales_m3' => null,
                'statut' => StatutReleveEau::Conforme,
            ];
        }

        $surfaceHa = (float) $campaign->surface_ha;

        $apportM3 = (float) Irrigation::withoutGlobalScope(ExploitationScope::class)
            ->where('exploitation_id', $campaign->exploitation_id)
            ->where('campaign_id', $campaign->getKey())
            ->where('production_unit_id', $campaign->parcelle_id)
            ->whereDate('date', '>', $precedent->date_releve->toDateString())
            ->whereDate('date', '<=', $dateReleve->toDateString())
            ->sum('volume_m3');

        $evaporationMm = $this->evaporationAttendue($campaign, $precedent->date_releve, $dateReleve);
        $percolationMm = $this->percolationAttendue($precedent->date_releve, $dateReleve);

        $apportMm = $surfaceHa > 0 ? $apportM3 / ($surfaceHa * self::M3_PAR_HA_MM) : 0.0;

        $niveauPrecedent = (float) $precedent->niveau_constate_mm;

        $niveauAttendu = round($niveauPrecedent + $precipitations + $apportMm - $evaporationMm - $percolationMm, 2);
        $ecart = round($niveauAttendu - $niveauConstate, 2);
        $tolerance = (float) config('irrigation.tolerance_ecart_niveau_mm', 15);

        $statut = StatutReleveEau::Conforme;

        if ($ecart > $tolerance) {
            $statut = StatutReleveEau::FuiteProbable;
        } elseif ($ecart < -$tolerance) {
            $statut = StatutReleveEau::SurAlimentation;
        }

        return [
            'phase_culturale' => $phase,
            'niveau_precedent_mm' => $niveauPrecedent,
            'apport_irrigation_m3' => round($apportM3, 2),
            'evaporation_attendue_mm' => round($evaporationMm, 2),
            'percolation_attendue_mm' => round($percolationMm, 2),
            'niveau_attendu_mm' => $niveauAttendu,
            'ecart_mm' => $ecart,
            'pertes_anormales_m3' => $ecart > 0 ? round($ecart * $surfaceHa * self::M3_PAR_HA_MM, 2) : 0.0,
            'statut' => $statut,
        ];
    }

    /**
     * Évaporation attendue sur l'intervalle couvert par le relevé : somme
     * des besoins journaliers des phases culturales traversées, du
     * lendemain du relevé précédent à la date du relevé courant inclus.
     */
    private function evaporationAttendue(CycleCulture $campaign, CarbonInterface $debut, CarbonInterface $fin): float
    {
        $total = 0.0;
        $jour = $debut->copy()->addDay();

        while ($jour->lessThanOrEqualTo($fin)) {
            $total += $this->phaseCulturale($campaign, $jour)->parametres()['besoin_journalier_mm'];
            $jour->addDay();
        }

        return $total;
    }

    private function percolationAttendue(CarbonInterface $debut, CarbonInterface $fin): float
    {
        $jours = (int) $debut->diffInDays($fin);

        return $jours * (float) config('irrigation.percolation_attendue_mm_jour', 2);
    }

    /**
     * Phase culturale traversée par la campagne à la date donnée : la
     * durée du cycle variétal est répartie entre les phases selon la
     * configuration d'irrigation ; au-delà du cycle, la maturation
     * s'applique par prudence.
     */
    private function phaseCulturale(CycleCulture $campaign, CarbonInterface $date): PhaseCulturale
    {
        $dureeCycle = (int) ($campaign->culture?->duree_cycle_jours ?? 0);

        if ($dureeCycle < self::JOURS_MINI_PAR_PHASE * count(PhaseCulturale::cases())) {
            throw new RegleMetierException('Le cycle de la culture est trop court pour suivre le niveau d\'eau.');
        }

        $joursEcoules = max(0, (int) $campaign->date_semis->diffInDays($date));

        $cumul = 0;

        foreach (PhaseCulturale::cases() as $phase) {
            $duree = max(self::JOURS_MINI_PAR_PHASE, (int) round($dureeCycle * $phase->parametres()['part_cycle']));
            $cumul += $duree;

            if ($joursEcoules < $cumul) {
                return $phase;
            }
        }

        return PhaseCulturale::Maturation;
    }
}
