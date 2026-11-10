<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CampaignController;
use App\Http\Controllers\Api\CycleCultureController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ParcelleController;
use App\Http\Controllers\Api\StockController;
use App\Http\Controllers\Api\VenteController;
use Illuminate\Support\Facades\Route;

Route::post('login', [AuthController::class, 'login'])
    ->middleware('throttle:10,1')
    ->name('api.login');

Route::prefix('v1')->name('api.')->group(function (): void {
    Route::post('auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1')
        ->name('auth.login');

    Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function (): void {
        Route::get('auth/me', [AuthController::class, 'me'])->name('auth.me');
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

        Route::get('dashboard', DashboardController::class)->name('dashboard');

        Route::get('campaigns', [CampaignController::class, 'index'])->name('campaigns.index');
        Route::get('campaigns/{campaign}', [CampaignController::class, 'show'])->name('campaigns.show');
        Route::get('campaigns/{campaign}/irrigations', [CampaignController::class, 'irrigations'])->name('campaigns.irrigations.index');
        Route::post('campaigns/{campaign}/irrigations', [CampaignController::class, 'storeIrrigation'])->name('campaigns.irrigations.store');

        Route::apiResource('parcelles', ParcelleController::class);

        Route::apiResource('cycles', CycleCultureController::class)->only(['index', 'show', 'store']);
        Route::post('cycles/{cycle}/recolte', [CycleCultureController::class, 'recolte'])->name('cycles.recolte');

        Route::apiResource('stocks', StockController::class)
            ->only(['index', 'show'])
            ->parameters(['stocks' => 'lot']);
        Route::get('stocks/{lot}/mouvements', [StockController::class, 'mouvements'])->name('stocks.mouvements');

        Route::apiResource('ventes', VenteController::class)->only(['index', 'show', 'store']);
        Route::post('ventes/{vente}/valider', [VenteController::class, 'valider'])->name('ventes.valider');
        Route::post('ventes/{vente}/annuler', [VenteController::class, 'annuler'])->name('ventes.annuler');
    });
});
