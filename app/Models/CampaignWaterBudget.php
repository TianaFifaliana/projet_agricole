<?php

namespace App\Models;

use App\Enums\StatutBudgetEau;
use App\Models\Concerns\BelongsToExploitation;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'exploitation_id',
    'campaign_id',
    'volume_alloue_m3',
    'volume_consomme_m3',
    'seuil_alerte_pct',
    'statut',
])]
class CampaignWaterBudget extends Model
{
    use BelongsToExploitation, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'volume_alloue_m3' => 'decimal:2',
            'volume_consomme_m3' => 'decimal:2',
            'seuil_alerte_pct' => 'decimal:2',
            'statut' => StatutBudgetEau::class,
        ];
    }

    public function exploitation(): BelongsTo
    {
        return $this->belongsTo(Exploitation::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(CycleCulture::class, 'campaign_id');
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(WaterBudgetAdjustment::class);
    }

    public function volumeRestant(): float
    {
        return max(0.0, round((float) $this->volume_alloue_m3 - (float) $this->volume_consomme_m3, 2));
    }

    public function pourcentageConsomme(): float
    {
        $alloue = (float) $this->volume_alloue_m3;

        if ($alloue <= 0) {
            return (float) $this->volume_consomme_m3 > 0 ? 100.0 : 0.0;
        }

        return round(((float) $this->volume_consomme_m3 / $alloue) * 100, 2);
    }

    public function estEpuise(): bool
    {
        return $this->volumeRestant() <= 0;
    }

    public function depasseSeuilAlerte(): bool
    {
        return $this->pourcentageConsomme() >= (float) $this->seuil_alerte_pct;
    }

    public function aRallongeEnAttente(): bool
    {
        return $this->adjustments()->where('statut', 'en_attente')->exists();
    }
}
