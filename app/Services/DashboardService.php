<?php

namespace App\Services;

use App\Enums\StatutCycleCulture;
use App\Models\CampaignWaterBudget;
use App\Models\CycleCulture;
use App\Models\JournalAudit;
use App\Models\Scopes\ExploitationScope;
use App\Support\ExploitationCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function invalider(int $exploitationId): void
    {
        ExploitationCache::invalidate($exploitationId);
    }

    /**
     * @return array<string, mixed>
     */
    public function statistiques(int $exploitationId): array
    {
        return Cache::remember(
            ExploitationCache::statsKey($exploitationId),
            now()->addMinutes(ExploitationCache::TTL_MINUTES),
            fn (): array => $this->calculerStatistiques($exploitationId),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function eau(int $exploitationId): array
    {
        return Cache::remember(
            ExploitationCache::waterKey($exploitationId),
            now()->addMinutes(ExploitationCache::TTL_MINUTES),
            fn (): array => $this->calculerEau($exploitationId),
        );
    }

    /**
     * Indicateurs propres à la riziculture, par campagne.
     *
     * @return array<string, mixed>
     */
    public function indicateurs(int $exploitationId): array
    {
        return Cache::remember(
            ExploitationCache::indicatorsKey($exploitationId),
            now()->addMinutes(ExploitationCache::TTL_MINUTES),
            fn (): array => $this->calculerIndicateurs($exploitationId),
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function activites(int $exploitationId): array
    {
        return Cache::remember(
            ExploitationCache::activitiesKey($exploitationId),
            now()->addMinutes(ExploitationCache::TTL_MINUTES),
            fn (): array => $this->calculerActivites($exploitationId),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function calculerStatistiques(int $exploitationId): array
    {
        $parcelles = DB::table('parcelles')
            ->where('exploitation_id', $exploitationId)
            ->where('actif', true)
            ->selectRaw('COUNT(*) as total, COALESCE(SUM(superficie_ha), 0) as surface')
            ->first();

        $campagnesActives = DB::table('cycles_culturaux')
            ->where('exploitation_id', $exploitationId)
            ->where('statut', 'en_cours')
            ->selectRaw('COUNT(*) as total, COALESCE(SUM(surface_ha), 0) as surface')
            ->first();

        $recoltes = DB::table('cycles_culturaux')
            ->where('exploitation_id', $exploitationId)
            ->whereYear('date_recolte_reelle', now()->year)
            ->sum('quantite_recoltee_kg');

        $ventesMois = DB::table('ventes')
            ->where('exploitation_id', $exploitationId)
            ->where('statut', 'validee')
            ->whereBetween('date_vente', [
                now()->startOfMonth()->toDateString(),
                now()->endOfMonth()->toDateString(),
            ])
            ->selectRaw('COUNT(*) as nombre, COALESCE(SUM(total_ttc), 0) as chiffre_affaires')
            ->first();

        $stocks = DB::table('intrants')
            ->where('intrants.exploitation_id', $exploitationId)
            ->where('intrants.actif', true)
            ->leftJoin('lots_stock', function ($join): void {
                $join->on('lots_stock.intrant_id', '=', 'intrants.id')
                    ->where('lots_stock.statut', 'disponible');
            })
            ->select('intrants.id', 'intrants.nom', 'intrants.categorie', 'intrants.unite', 'intrants.seuil_alerte')
            ->selectRaw('COALESCE(SUM(lots_stock.quantite_restante), 0) as stock_disponible')
            ->groupBy('intrants.id', 'intrants.nom', 'intrants.categorie', 'intrants.unite', 'intrants.seuil_alerte')
            ->orderBy('intrants.categorie')
            ->orderBy('intrants.nom')
            ->get()
            ->map(fn ($intrant): array => [
                'intrant_id' => (int) $intrant->id,
                'nom' => $intrant->nom,
                'categorie' => $intrant->categorie,
                'unite' => $intrant->unite,
                'seuil_alerte' => round((float) $intrant->seuil_alerte, 2),
                'stock_disponible' => round((float) $intrant->stock_disponible, 2),
                'critique' => (float) $intrant->seuil_alerte > 0
                    && (float) $intrant->stock_disponible <= (float) $intrant->seuil_alerte,
            ]);

        $animauxActifs = DB::table('animaux')
            ->where('exploitation_id', $exploitationId)
            ->where('statut', 'actif')
            ->count();

        $evenementsSanitaires = DB::table('evenements_sanitaires')
            ->where('exploitation_id', $exploitationId)
            ->whereBetween('date_evenement', [
                now()->startOfMonth()->toDateString(),
                now()->endOfMonth()->toDateString(),
            ])
            ->count();

        return [
            'parcelles' => [
                'total' => (int) $parcelles->total,
                'surface_ha' => round((float) $parcelles->surface, 2),
            ],
            'campagnes_actives' => [
                'total' => (int) $campagnesActives->total,
                'superficie_ha' => round((float) $campagnesActives->surface, 2),
            ],
            'recoltes_kg' => round((float) $recoltes, 2),
            'ventes_mois' => [
                'nombre' => (int) $ventesMois->nombre,
                'chiffre_affaires' => round((float) $ventesMois->chiffre_affaires, 2),
            ],
            'stocks' => $stocks->all(),
            'alertes_stock' => $stocks->filter(fn (array $intrant): bool => $intrant['critique'])->values()->all(),
            'animaux_actifs' => $animauxActifs,
            'evenements_sanitaires_mois' => $evenementsSanitaires,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function calculerEau(int $exploitationId): array
    {
        $budgets = CampaignWaterBudget::withoutGlobalScope(ExploitationScope::class)
            ->where('exploitation_id', $exploitationId)
            ->whereHas('campaign', fn (Builder $query): Builder => $query
                ->withoutGlobalScope(ExploitationScope::class)
                ->where('statut', StatutCycleCulture::EnCours->value))
            ->with(['campaign.culture', 'campaign.parcelle'])
            ->orderBy('campaign_id')
            ->get();

        $campagnes = $budgets->map(fn (CampaignWaterBudget $budget): array => [
            'budget_id' => $budget->getKey(),
            'campaign_id' => $budget->campaign_id,
            'campagne' => trim(sprintf(
                '%s %s',
                $budget->campaign?->culture?->nom ?? '',
                $budget->campaign?->campagne ?? '',
            )),
            'parcelle' => $budget->campaign?->parcelle?->code,
            'volume_alloue_m3' => (float) $budget->volume_alloue_m3,
            'volume_consomme_m3' => (float) $budget->volume_consomme_m3,
            'volume_restant_m3' => $budget->volumeRestant(),
            'pourcentage_consomme' => $budget->pourcentageConsomme(),
            'seuil_alerte_pct' => (float) $budget->seuil_alerte_pct,
            'statut' => $budget->statut->value,
        ])->all();

        return [
            'campagnes' => $campagnes,
            'total_alloue_m3' => round(array_sum(array_column($campagnes, 'volume_alloue_m3')), 2),
            'total_consomme_m3' => round(array_sum(array_column($campagnes, 'volume_consomme_m3')), 2),
            'total_restant_m3' => round(array_sum(array_column($campagnes, 'volume_restant_m3')), 2),
            'alertes_quotas' => collect($campagnes)
                ->filter(fn (array $campagne): bool => $campagne['pourcentage_consomme'] >= $campagne['seuil_alerte_pct'])
                ->values()
                ->all(),
        ];
    }

    /**
     * Indicateurs calculés propres à la riziculture :
     * consommation d'eau par hectare (m³/ha), rendement (t/ha)
     * et productivité de l'eau (kg de paddy par m³).
     *
     * @return array<string, mixed>
     */
    private function calculerIndicateurs(int $exploitationId): array
    {
        $cycles = CycleCulture::withoutGlobalScope(ExploitationScope::class)
            ->where('exploitation_id', $exploitationId)
            ->with(['culture', 'parcelle', 'waterBudget'])
            ->orderByDesc('campagne')
            ->orderByDesc('date_semis')
            ->limit(12)
            ->get();

        $campagnes = $cycles->map(fn (CycleCulture $cycle): array => [
            'cycle_id' => $cycle->getKey(),
            'campagne' => trim(sprintf('%s %s', $cycle->culture?->nom ?? '', $cycle->campagne)),
            'parcelle' => $cycle->parcelle?->code,
            'statut' => $cycle->statut->value,
            'statut_label' => $cycle->statut->label(),
            'surface_ha' => round((float) $cycle->surface_ha, 2),
            'volume_alloue_m3' => round((float) ($cycle->waterBudget?->volume_alloue_m3 ?? 0), 2),
            'volume_consomme_m3' => round((float) ($cycle->waterBudget?->volume_consomme_m3 ?? 0), 2),
            'quantite_recoltee_kg' => round((float) ($cycle->quantite_recoltee_kg ?? 0), 2),
            'consommation_eau_m3_ha' => $this->consommationEauParHectare($cycle),
            'rendement_tha' => $this->rendementParHectare($cycle),
            'productivite_eau_kg_m3' => $this->productiviteEau($cycle),
        ])->all();

        $surfaceTotale = array_sum(array_column($campagnes, 'surface_ha'));
        $volumeTotal = array_sum(array_column($campagnes, 'volume_consomme_m3'));
        $paddyTotal = array_sum(array_column($campagnes, 'quantite_recoltee_kg'));

        return [
            'campagnes' => $campagnes,
            'consommation_eau_m3_ha' => $surfaceTotale > 0 ? round($volumeTotal / $surfaceTotale, 2) : null,
            'rendement_tha' => $surfaceTotale > 0 ? round($paddyTotal / 1000 / $surfaceTotale, 2) : null,
            'productivite_eau_kg_m3' => $volumeTotal > 0 ? round($paddyTotal / $volumeTotal, 2) : null,
        ];
    }

    /**
     * Consommation d'eau par hectare : volume d'eau utilisé / superficie (m³/ha).
     */
    private function consommationEauParHectare(CycleCulture $cycle): ?float
    {
        $surface = (float) $cycle->surface_ha;
        $volume = (float) ($cycle->waterBudget?->volume_consomme_m3 ?? 0);

        if ($surface <= 0 || $volume <= 0) {
            return null;
        }

        return round($volume / $surface, 2);
    }

    /**
     * Rendement : quantité de paddy récoltée / superficie (t/ha).
     */
    private function rendementParHectare(CycleCulture $cycle): ?float
    {
        $surface = (float) $cycle->surface_ha;
        $quantite = (float) ($cycle->quantite_recoltee_kg ?? 0);

        if ($surface <= 0 || $quantite <= 0) {
            return null;
        }

        return round($quantite / 1000 / $surface, 2);
    }

    /**
     * Productivité de l'eau : kg de paddy par m³ d'eau.
     */
    private function productiviteEau(CycleCulture $cycle): ?float
    {
        $volume = (float) ($cycle->waterBudget?->volume_consomme_m3 ?? 0);
        $quantite = (float) ($cycle->quantite_recoltee_kg ?? 0);

        if ($volume <= 0 || $quantite <= 0) {
            return null;
        }

        return round($quantite / $volume, 2);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function calculerActivites(int $exploitationId): array
    {
        return JournalAudit::query()
            ->where('exploitation_id', $exploitationId)
            ->with('user')
            ->latest('created_at')
            ->limit(8)
            ->get()
            ->map(fn (JournalAudit $entree): array => [
                'action' => $entree->action,
                'entite' => class_basename($entree->entite),
                'date' => $entree->created_at?->format('d/m/Y H:i'),
                'utilisateur' => $entree->user?->nomComplet() ?? 'Système',
            ])->all();
    }
}
