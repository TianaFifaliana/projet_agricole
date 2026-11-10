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

        $statistiques = $dashboard->statistiques($exploitationId);
        $indicateurs = $dashboard->indicateurs($exploitationId);
        $evolution = $finance->evolutionMensuelle($exploitationId);

        return view('dashboard', [
            'exploitation' => $user->exploitation,
            'statistiques' => $statistiques,
            'eau' => $dashboard->eau($exploitationId),
            'indicateurs' => $indicateurs,
            'activites' => $dashboard->activites($exploitationId),
            'marge' => $finance->marge($exploitationId),
            'evolution' => $evolution,
            'charts' => [
                'quotaEau' => [
                    'labels' => array_column($indicateurs['campagnes'], 'campagne'),
                    'alloue' => array_column($indicateurs['campagnes'], 'volume_alloue_m3'),
                    'consomme' => array_column($indicateurs['campagnes'], 'volume_consomme_m3'),
                ],
                'finances' => [
                    'labels' => $evolution['mois'],
                    'recettes' => $evolution['recettes'],
                    'depenses' => $evolution['depenses'],
                ],
                'rendement' => [
                    'labels' => array_column($indicateurs['campagnes'], 'campagne'),
                    'rendements' => array_column($indicateurs['campagnes'], 'rendement_tha'),
                ],
            ],
        ]);
    }
}
