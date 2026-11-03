<?php

namespace App\Http\Controllers;

use App\Models\Intrant;
use App\Models\LotStock;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockController extends Controller
{
    public function index(Request $request, DashboardService $dashboard): View
    {
        $this->authorize('viewAny', LotStock::class);

        $exploitationId = $request->user()->exploitation_id;
        $statistiques = $exploitationId === null ? null : $dashboard->statistiques($exploitationId);

        return view('stocks.index', [
            'lots' => LotStock::query()
                ->with(['intrant', 'parcelle', 'cycleCulture'])
                ->orderByDesc('date_entree')
                ->paginate(20),
            'intrants' => Intrant::query()->where('actif', true)->orderBy('nom')->get(),
            'alertes' => $statistiques['alertes_stock'] ?? [],
        ]);
    }
}
