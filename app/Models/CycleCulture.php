<?php

namespace App\Models;

use App\Enums\StatutCycleCulture;
use App\Models\Concerns\BelongsToExploitation;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'exploitation_id',
    'parcelle_id',
    'culture_id',
    'campagne',
    'date_semis',
    'date_recolte_prevue',
    'date_recolte_reelle',
    'statut',
    'surface_ha',
    'rendement_obtenu_tha',
    'quantite_recoltee_kg',
    'notes',
])]
class CycleCulture extends Model
{
    use BelongsToExploitation, HasFactory, SoftDeletes;

    protected $table = 'cycles_culturaux';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_semis' => 'date',
            'date_recolte_prevue' => 'date',
            'date_recolte_reelle' => 'date',
            'statut' => StatutCycleCulture::class,
            'surface_ha' => 'decimal:2',
            'rendement_obtenu_tha' => 'decimal:2',
            'quantite_recoltee_kg' => 'decimal:2',
        ];
    }

    public function exploitation(): BelongsTo
    {
        return $this->belongsTo(Exploitation::class);
    }

    public function parcelle(): BelongsTo
    {
        return $this->belongsTo(Parcelle::class);
    }

    public function culture(): BelongsTo
    {
        return $this->belongsTo(Culture::class);
    }

    public function interventions(): HasMany
    {
        return $this->hasMany(Intervention::class);
    }

    public function lotsStock(): HasMany
    {
        return $this->hasMany(LotStock::class);
    }

    public function waterBudget(): HasOne
    {
        return $this->hasOne(CampaignWaterBudget::class, 'campaign_id');
    }

    public function irrigations(): HasMany
    {
        return $this->hasMany(Irrigation::class, 'campaign_id');
    }

    public function scopeParCampagne(Builder $query, string $campagne): Builder
    {
        return $query->where('campagne', $campagne);
    }

    public function scopeEnCours(Builder $query): Builder
    {
        return $query->where('statut', StatutCycleCulture::EnCours->value);
    }

    public function estRecolte(): bool
    {
        return in_array($this->statut, [StatutCycleCulture::Recolte, StatutCycleCulture::Cloture], true);
    }
}
