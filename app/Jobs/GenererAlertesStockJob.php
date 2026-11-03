<?php

namespace App\Jobs;

use App\Enums\StatutLotStock;
use App\Events\StockSousSeuil;
use App\Models\Exploitation;
use App\Models\Intrant;
use App\Models\LotStock;
use App\Models\Scopes\ExploitationScope;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenererAlertesStockJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function handle(): void
    {
        Exploitation::query()
            ->where('actif', true)
            ->chunkById(100, function ($exploitations): void {
                foreach ($exploitations as $exploitation) {
                    $intrants = Intrant::withoutGlobalScope(ExploitationScope::class)
                        ->where('exploitation_id', $exploitation->getKey())
                        ->where('actif', true)
                        ->where('seuil_alerte', '>', 0)
                        ->get();

                    foreach ($intrants as $intrant) {
                        $disponible = (float) LotStock::withoutGlobalScope(ExploitationScope::class)
                            ->where('intrant_id', $intrant->getKey())
                            ->where('statut', StatutLotStock::Disponible->value)
                            ->sum('quantite_restante');

                        if ($disponible <= (float) $intrant->seuil_alerte) {
                            StockSousSeuil::dispatch($intrant, $disponible);
                        }
                    }
                }
            });
    }
}
