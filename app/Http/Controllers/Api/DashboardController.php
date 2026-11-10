<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use App\Services\FinanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, DashboardService $dashboard, FinanceService $finance): JsonResponse
    {
        $exploitationId = $request->user()->exploitation_id
            ?? $request->integer('exploitation_id');

        abort_if($exploitationId === 0, 422, 'Une exploitation est requise.');

        return response()->json([
            'data' => [
                ...$dashboard->statistiques($exploitationId),
                'eau' => $dashboard->eau($exploitationId),
                'marge' => $finance->marge($exploitationId),
            ],
        ]);
    }
}
