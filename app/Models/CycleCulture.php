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

    protected \ = 'cycles_culturaux';

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
        return \->belongsTo(Exploitation::class);
    }

    public function parcelle(): BelongsTo
    {
        return \->belongsTo(Parcelle::class);
    }

    public function culture(): BelongsTo
    {
        return \->belongsTo(Culture::class);
    }

    public function interventions(): HasMany
    {
        return \->hasMany(Intervention::class);
    }

    public function lotsStock(): HasMany
    {
        return \->hasMany(LotStock::class);
    }

    public function scopeParCampagne(Builder \, string \): Builder
    {
        return \->where('campagne', \);
    }

    public function scopeEnCours(Builder \): Builder
    {
        return \->where('statut', StatutCycleCulture::EnCours->value);
    }

    public function estRecolte(): bool
    {
        return in_array(\->statut, [StatutCycleCulture::Recolte, StatutCycleCulture::Cloture], true);
    }
}
