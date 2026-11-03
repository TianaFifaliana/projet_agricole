<?php

namespace App\Http\Controllers;

use App\Models\Exploitation;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AlerteController extends Controller
{
    public function index(Request $request, DashboardService $dashboard): View
    {
        $user = $request->user();
        $exploitationId = $user->exploitation_id;

        if ($user->isAdministrateur()) {
            $alertes = Exploitation::query()
                ->pluck('id')
                ->flatMap(fn (int $id): array => $dashboard->statistiques($id)['alertes_stock'])
                ->values()
                ->all();
        } else {
            abort_if($exploitationId === null, 403);

            $alertes = $dashboard->statistiques($exploitationId)['alertes_stock'];
        }

        return view('alertes.index', [
            'alertes' => $alertes,
            'notifications' => $user->notifications()->latest()->limit(25)->get(),
        ]);
    }
}
