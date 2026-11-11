<?php

namespace App\Notifications;

use App\Models\ReleveNiveauEau;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FuiteEauDetecteeNotification extends Notification
{
    use Queueable;

    public function __construct(public ReleveNiveauEau $releve) {}

    /**
     * Database alimente la cloche de l'application, mail porte l'urgence hors connexion.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $riziere = $this->riziereLabel();

        return (new MailMessage)
            ->subject("Fuite probable détectée : rizière {$riziere}")
            ->greeting('Fuite probable détectée')
            ->line($this->message())
            ->line(sprintf(
                'Niveau attendu : %s mm — Niveau constaté : %s mm.',
                number_format((float) $this->releve->niveau_attendu_mm, 2, ',', ' '),
                number_format((float) $this->releve->niveau_constate_mm, 2, ',', ' '),
            ))
            ->action('Consulter les alertes', route('alertes.index'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'fuite_eau',
            'releve_id' => $this->releve->getKey(),
            'campaign_id' => $this->releve->campaign_id,
            'parcelle_id' => $this->releve->parcelle_id,
            'riziere' => $this->riziereLabel(),
            'campagne' => $this->campagneLabel(),
            'date_releve' => $this->releve->date_releve?->toDateString(),
            'phase_culturale' => $this->releve->phase_culturale?->label(),
            'niveau_attendu_mm' => (float) $this->releve->niveau_attendu_mm,
            'niveau_constate_mm' => (float) $this->releve->niveau_constate_mm,
            'ecart_mm' => (float) $this->releve->ecart_mm,
            'pertes_estimees_m3' => (float) $this->releve->pertes_anormales_m3,
            'message' => $this->message(),
        ];
    }

    private function message(): string
    {
        return sprintf(
            'Fuite probable : %s mm manquants dans la rizière %s (niveau attendu %s mm, constaté %s mm), pertes estimées à %s m³.',
            number_format((float) $this->releve->ecart_mm, 2, ',', ' '),
            $this->riziereLabel(),
            number_format((float) $this->releve->niveau_attendu_mm, 2, ',', ' '),
            number_format((float) $this->releve->niveau_constate_mm, 2, ',', ' '),
            number_format((float) $this->releve->pertes_anormales_m3, 2, ',', ' '),
        );
    }

    private function riziereLabel(): string
    {
        $parcelle = $this->releve->parcelle;

        return $parcelle?->code ?? "rizière #{$this->releve->parcelle_id}";
    }

    private function campagneLabel(): string
    {
        $campagne = $this->releve->campaign;

        if ($campagne === null) {
            return "campagne #{$this->releve->campaign_id}";
        }

        $libelle = trim(sprintf('%s %s', $campagne->culture?->nom ?? '', $campagne->campagne));

        return $libelle !== '' ? $libelle : "campagne #{$campagne->getKey()}";
    }
}
