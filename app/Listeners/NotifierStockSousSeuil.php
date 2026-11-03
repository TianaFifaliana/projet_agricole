<?php

namespace App\Listeners;

use App\Enums\UserRole;
use App\Events\StockSousSeuil;
use App\Models\User;
use App\Notifications\StockSousSeuilNotification;
use Illuminate\Support\Facades\Notification;

class NotifierStockSousSeuil
{
    public function handle(StockSousSeuil $event): void
    {
        $responsables = User::query()
            ->where('exploitation_id', $event->intrant->exploitation_id)
            ->where('role', UserRole::Responsable->value)
            ->where('actif', true)
            ->get();

        if ($responsables->isEmpty()) {
            return;
        }

        Notification::send(
            $responsables,
            new StockSousSeuilNotification($event->intrant, $event->quantiteDisponible),
        );
    }
}
