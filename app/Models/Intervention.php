<?php

namespace App\Models;

use App\Enums\TypeIntervention;
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
    'cycle_culture_id',
    'intrant_id',
    'operateur_id',
    'type',
    'date_intervention',
    'quantite',
    'unite',
    'cout',
    'description',
])]
class Intervention extends Model
{
    use BelongsToExploitation, HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => TypeIntervention::class,
            'date_intervention' => 'date',
            'quantite' => 'decimal:2',
            'cout' => 'decimal:2',
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

    public function cycleCulture(): BelongsTo
    {
        return \->belongsTo(CycleCulture::class, 'cycle_culture_id');
    }

    public function intrant(): BelongsTo
    {
        return \->belongsTo(Intrant::class);
    }

    public function operateur(): BelongsTo
    {
        return \->belongsTo(User::class, 'operateur_id');
    }

    public function mouvementsStock(): HasMany
    {
        return \->hasMany(MouvementStock::class);
    }

    public function lotsStock(): HasMany
    {
        return \->hasMany(LotStock::class);
    }

    public function scopeRecoltes(Builder \): Builder
    {
        return \->where('type', TypeIntervention::Recolte->value);
    }

    public function scopeParPeriode(Builder \, \, \): Builder
    {
        return \->whereBetween('date_intervention', [\, \]);
    }
}
