<?php

namespace App\Listeners;

use App\Events\RecolteEnregistree;
use App\Events\StockSousSeuil;
use App\Events\VenteAnnulee;
use App\Events\VenteValidee;
use App\Services\DashboardService;
use Illuminate\Events\Dispatcher;

class InvalidationCacheSubscriber
{
    public function __construct(private DashboardService $dashboard) {}

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(RecolteEnregistree::class, [self::class, 'handleRecolte']);
        $events->listen(VenteValidee::class, [self::class, 'handleVente']);
        $events->listen(VenteAnnulee::class, [self::class, 'handleAnnulationVente']);
        $events->listen(StockSousSeuil::class, [self::class, 'handleStock']);
    }

    public function handleRecolte(RecolteEnregistree $event): void
    {
        $this->dashboard->invalider($event->cycle->exploitation_id);
    }

    public function handleVente(VenteValidee $event): void
    {
        $this->dashboard->invalider($event->vente->exploitation_id);
    }

    public function handleAnnulationVente(VenteAnnulee $event): void
    {
        $this->dashboard->invalider($event->vente->exploitation_id);
    }

    public function handleStock(StockSousSeuil $event): void
    {
        $this->dashboard->invalider($event->intrant->exploitation_id);
    }
}
