<?php

namespace App\Http\Controllers;

use App\Models\Exploitation;
use App\Services\DashboardService;
use App\Services\FinanceService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, DashboardService $dashboard, FinanceService $finance): View
    {
        $user = $request->user();

        if ($user->isAdministrateur()) {
            return view('dashboard-admin', [
                'exploitations' => Exploitation::query()
                    ->withCount(['users', 'parcelles', 'cyclesCulturaux', 'ventes'])
                    ->orderBy('nom')
                    ->get(),
            ]);
        }

        $exploitationId = $user->exploitation_id;

        abort_if($exploitationId === null, 403, "Aucune exploitation n'est rattachée à votre compte.");

        return view('dashboard', [
            'exploitation' => $user->exploitation,
            'statistiques' => $dashboard->statistiques($exploitationId),
            'eau' => $dashboard->eau($exploitationId),
            'marge' => $finance->marge($exploitationId),
        ]);
    }
}
