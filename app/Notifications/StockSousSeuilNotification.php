<?php

namespace App\Notifications;

use App\Models\Intrant;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StockSousSeuilNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Intrant $intrant,
        public float $quantiteDisponible,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Alerte stock : {$this->intrant->nom}")
            ->greeting('Alerte de stock')
            ->line(sprintf(
                'Le stock de %s est descendu à %s %s.',
                $this->intrant->nom,
                number_format($this->quantiteDisponible, 2, ',', ' '),
                $this->intrant->unite,
            ))
            ->line(sprintf(
                "Seuil d'alerte configuré : %s %s.",
                number_format((float) $this->intrant->seuil_alerte, 2, ',', ' '),
                $this->intrant->unite,
            ))
            ->action('Consulter les stocks', route('stocks.index'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'intrant_id' => $this->intrant->getKey(),
            'intrant' => $this->intrant->nom,
            'quantite_disponible' => $this->quantiteDisponible,
            'seuil_alerte' => (float) $this->intrant->seuil_alerte,
            'unite' => $this->intrant->unite,
        ];
    }
}
