<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public const TTL_MINUTES = 10;

    public static function cacheKey(int $exploitationId): string
    {
        return "dashboard:exploitation:{$exploitationId}";
    }

    public function invalider(int $exploitationId): void
    {
        Cache::forget(self::cacheKey($exploitationId));
    }

    /**
     * @return array<string, mixed>
     */
    public function statistiques(int $exploitationId): array
    {
        return Cache::remember(
            self::cacheKey($exploitationId),
            now()->addMinutes(self::TTL_MINUTES),
            fn (): array => $this->calculer($exploitationId),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function calculer(int $exploitationId): array
    {
        $parcelles = DB::table('parcelles')
            ->where('exploitation_id', $exploitationId)
            ->where('actif', true)
            ->selectRaw('COUNT(*) as total, COALESCE(SUM(superficie_ha), 0) as surface')
            ->first();

        $cyclesEnCours = DB::table('cycles_culturaux')
            ->where('exploitation_id', $exploitationId)
            ->where('statut', 'en_cours')
            ->count();

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

        $alertes = DB::table('intrants')
            ->where('intrants.exploitation_id', $exploitationId)
            ->where('intrants.actif', true)
            ->where('intrants.seuil_alerte', '>', 0)
            ->leftJoin('lots_stock', function ($join): void {
                $join->on('lots_stock.intrant_id', '=', 'intrants.id')
                    ->where('lots_stock.statut', 'disponible');
            })
            ->select('intrants.id', 'intrants.nom', 'intrants.unite', 'intrants.seuil_alerte')
            ->selectRaw('COALESCE(SUM(lots_stock.quantite_restante), 0) as stock_disponible')
            ->groupBy('intrants.id', 'intrants.nom', 'intrants.unite', 'intrants.seuil_alerte')
            ->havingRaw('COALESCE(SUM(lots_stock.quantite_restante), 0) <= intrants.seuil_alerte')
            ->orderBy('intrants.nom')
            ->get();

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
            'cycles_en_cours' => $cyclesEnCours,
            'recoltes_kg' => round((float) $recoltes, 2),
            'ventes_mois' => [
                'nombre' => (int) $ventesMois->nombre,
                'chiffre_affaires' => round((float) $ventesMois->chiffre_affaires, 2),
            ],
            'alertes_stock' => $alertes->map(fn ($alerte): array => [
                'intrant_id' => (int) $alerte->id,
                'nom' => $alerte->nom,
                'unite' => $alerte->unite,
                'seuil_alerte' => round((float) $alerte->seuil_alerte, 2),
                'stock_disponible' => round((float) $alerte->stock_disponible, 2),
            ])->all(),
            'animaux_actifs' => $animauxActifs,
            'evenements_sanitaires_mois' => $evenementsSanitaires,
        ];
    }
}
