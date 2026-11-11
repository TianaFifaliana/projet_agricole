<?php

namespace App\Models;

use App\Enums\PhaseCulturale;
use App\Enums\StatutReleveEau;
use App\Models\Concerns\BelongsToExploitation;
use App\Observers\ReleveNiveauEauObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy(ReleveNiveauEauObserver::class)]
#[Fillable([
    'exploitation_id',
    'campaign_id',
    'parcelle_id',
    'user_id',
    'date_releve',
    'niveau_constate_mm',
    'precipitations_mm',
    'phase_culturale',
    'niveau_precedent_mm',
    'apport_irrigation_m3',
    'evaporation_attendue_mm',
    'percolation_attendue_mm',
    'niveau_attendu_mm',
    'ecart_mm',
    'pertes_anormales_m3',
    'statut',
    'observation',
])]
class ReleveNiveauEau extends Model
{
    use BelongsToExploitation, HasFactory, SoftDeletes;

    protected $table = 'releves_niveau_eau';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_releve' => 'date',
            'niveau_constate_mm' => 'decimal:2',
            'precipitations_mm' => 'decimal:2',
            'phase_culturale' => PhaseCulturale::class,
            'niveau_precedent_mm' => 'decimal:2',
            'apport_irrigation_m3' => 'decimal:2',
            'evaporation_attendue_mm' => 'decimal:2',
            'percolation_attendue_mm' => 'decimal:2',
            'niveau_attendu_mm' => 'decimal:2',
            'ecart_mm' => 'decimal:2',
            'pertes_anormales_m3' => 'decimal:2',
            'statut' => StatutReleveEau::class,
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

    public function parcelle(): BelongsTo
    {
        return $this->belongsTo(Parcelle::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeParCampagne(Builder $query, int $campaignId): Builder
    {
        return $query->where('campaign_id', $campaignId);
    }

    public function scopeParParcelle(Builder $query, int $parcelleId): Builder
    {
        return $query->where('parcelle_id', $parcelleId);
    }

    /**
     * Premier relevé d'une campagne : il fixe la référence, sans bilan.
     */
    public function estPremier(): bool
    {
        return $this->niveau_precedent_mm === null;
    }

    public function estFuiteProbable(): bool
    {
        return $this->statut === StatutReleveEau::FuiteProbable;
    }

    /**
     * Pertes hydriques attendues sur l'intervalle couvert par le relevé,
     * exprimées en millimètres (évaporation + percolation).
     */
    public function pertesNormalesMm(): float
    {
        return round((float) $this->evaporation_attendue_mm + (float) $this->percolation_attendue_mm, 2);
    }
}
