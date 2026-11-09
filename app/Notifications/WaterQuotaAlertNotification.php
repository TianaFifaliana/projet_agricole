<?php

namespace App\Notifications;

use App\Models\CampaignWaterBudget;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WaterQuotaAlertNotification extends Notification
{
    use Queueable;

    public function __construct(
        public CampaignWaterBudget $budget,
        public bool $quotaEpuise = false,
    ) {}

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
        $campagne = $this->campagneLabel();
        $pourcentage = $this->pourcentageFormate();
        $restant = number_format($this->budget->volumeRestant(), 2, ',', ' ');

        $message = $this->quotaEpuise
            ? "Quota d'eau épuisé : la campagne {$campagne} a consommé {$pourcentage} % de son quota d'eau ({$restant} m³ restants)."
            : "Alerte : la campagne {$campagne} a consommé {$pourcentage} % de son quota d'eau ({$restant} m³ restants).";

        return (new MailMessage)
            ->subject($this->quotaEpuise ? "Quota d'eau épuisé : {$campagne}" : "Alerte quota d'eau : {$campagne}")
            ->greeting($this->quotaEpuise ? 'Quota d\'eau épuisé' : 'Alerte quota d\'eau')
            ->line($message)
            ->line(sprintf(
                'Volume alloué : %s m³ — Volume consommé : %s m³.',
                number_format((float) $this->budget->volume_alloue_m3, 2, ',', ' '),
                number_format((float) $this->budget->volume_consomme_m3, 2, ',', ' '),
            ))
            ->action('Consulter les quotas d\'eau', route('water-budgets.index'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $campagne = $this->campagneLabel();
        $pourcentage = $this->pourcentageFormate();
        $restant = number_format($this->budget->volumeRestant(), 2, ',', ' ');

        return [
            'type' => 'water_quota',
            'niveau' => $this->quotaEpuise ? 'epuise' : 'critique',
            'budget_id' => $this->budget->getKey(),
            'campaign_id' => $this->budget->campaign_id,
            'campagne' => $campagne,
            'pourcentage_consomme' => $this->budget->pourcentageConsomme(),
            'volume_alloue_m3' => (float) $this->budget->volume_alloue_m3,
            'volume_consomme_m3' => (float) $this->budget->volume_consomme_m3,
            'volume_restant_m3' => $this->budget->volumeRestant(),
            'seuil_alerte_pct' => (float) $this->budget->seuil_alerte_pct,
            'message' => $this->quotaEpuise
                ? "Quota d'eau épuisé : la campagne {$campagne} a consommé {$pourcentage} % de son quota d'eau ({$restant} m³ restants)."
                : "Alerte : la campagne {$campagne} a consommé {$pourcentage} % de son quota d'eau ({$restant} m³ restants).",
        ];
    }

    private function campagneLabel(): string
    {
        $campagne = $this->budget->campaign;

        if ($campagne === null) {
            return "campagne #{$this->budget->campaign_id}";
        }

        $libelle = trim(sprintf('%s %s', $campagne->culture?->nom ?? '', $campagne->campagne));

        return $libelle !== '' ? $libelle : "campagne #{$campagne->getKey()}";
    }

    private function pourcentageFormate(): string
    {
        return rtrim(rtrim(number_format($this->budget->pourcentageConsomme(), 2, ',', ' '), '0'), ',');
    }
}
