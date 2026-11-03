<?php

use App\Http\Controllers\AlerteController;
use App\Http\Controllers\AnimalController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CycleCultureController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InterventionController;
use App\Http\Controllers\ParcelleController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\VenteController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/', DashboardController::class)->name('dashboard');

    Route::resource('parcelles', ParcelleController::class)->except(['show']);

    Route::get('cycles', [CycleCultureController::class, 'index'])->name('cycles.index');
    Route::get('cycles/create', [CycleCultureController::class, 'create'])->name('cycles.create');
    Route::post('cycles', [CycleCultureController::class, 'store'])->name('cycles.store');
    Route::get('cycles/{cycle}', [CycleCultureController::class, 'show'])->name('cycles.show');
    Route::post('cycles/{cycle}/recolte', [CycleCultureController::class, 'recolte'])->name('cycles.recolte');

    Route::get('interventions', [InterventionController::class, 'index'])->name('interventions.index');
    Route::post('interventions', [InterventionController::class, 'store'])->name('interventions.store');

    Route::get('stocks', [StockController::class, 'index'])->name('stocks.index');

    Route::get('ventes', [VenteController::class, 'index'])->name('ventes.index');
    Route::get('ventes/create', [VenteController::class, 'create'])->name('ventes.create');
    Route::post('ventes', [VenteController::class, 'store'])->name('ventes.store');
    Route::get('ventes/{vente}', [VenteController::class, 'show'])->name('ventes.show');
    Route::post('ventes/{vente}/valider', [VenteController::class, 'valider'])->name('ventes.valider');
    Route::post('ventes/{vente}/annuler', [VenteController::class, 'annuler'])->name('ventes.annuler');

    Route::get('animaux', [AnimalController::class, 'index'])->name('animaux.index');
    Route::get('alertes', [AlerteController::class, 'index'])->name('alertes.index');
});
