<?php

namespace App\Listeners;

use App\Events\RecolteEnregistree;
use App\Events\VenteAnnulee;
use App\Events\VenteValidee;
use App\Services\AuditService;
use Illuminate\Events\Dispatcher;

class JournalisationSubscriber
{
    public function __construct(private AuditService $audit) {}

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(RecolteEnregistree::class, [self::class, 'handleRecolte']);
        $events->listen(VenteValidee::class, [self::class, 'handleVenteValidee']);
        $events->listen(VenteAnnulee::class, [self::class, 'handleVenteAnnulee']);
    }

    public function handleRecolte(RecolteEnregistree $event): void
    {
        $this->audit->journaliser(
            action: 'recolte',
            entite: $event->intervention,
            nouvellesValeurs: [
                'cycle_culture_id' => $event->cycle->getKey(),
                'quantite_kg' => (float) $event->intervention->quantite,
                'campagne' => $event->cycle->campagne,
            ],
            exploitationId: $event->cycle->exploitation_id,
        );
    }

    public function handleVenteValidee(VenteValidee $event): void
    {
        $this->audit->journaliser(
            action: 'validation_vente',
            entite: $event->vente,
            nouvellesValeurs: [
                'statut' => 'validee',
                'total_ttc' => (float) $event->vente->total_ttc,
            ],
            exploitationId: $event->vente->exploitation_id,
        );
    }

    public function handleVenteAnnulee(VenteAnnulee $event): void
    {
        $this->audit->journaliser(
            action: 'annulation_vente',
            entite: $event->vente,
            nouvellesValeurs: [
                'statut' => 'annulee',
            ],
            exploitationId: $event->vente->exploitation_id,
        );
    }
}
