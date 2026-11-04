<?php

namespace App\Models;

use App\Enums\StatutLotStock;
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
    'intrant_id',
    'parcelle_id',
    'cycle_culture_id',
    'intervention_id',
    'code_lot',
    'quantite_initiale',
    'quantite_restante',
    'unite',
    'date_entree',
    'date_peremption',
    'origine',
    'prix_unitaire',
    'statut',
])]
class LotStock extends Model
{
    use BelongsToExploitation, HasFactory, SoftDeletes;

    protected \ = 'lots_stock';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_entree' => 'date',
            'date_peremption' => 'date',
            'quantite_initiale' => 'decimal:2',
            'quantite_restante' => 'decimal:2',
            'prix_unitaire' => 'decimal:2',
            'statut' => StatutLotStock::class,
        ];
    }

    public function exploitation(): BelongsTo
    {
        return \->belongsTo(Exploitation::class);
    }

    public function intrant(): BelongsTo
    {
        return \->belongsTo(Intrant::class);
    }

    public function parcelle(): BelongsTo
    {
        return \->belongsTo(Parcelle::class);
    }

    public function cycleCulture(): BelongsTo
    {
        return \->belongsTo(CycleCulture::class, 'cycle_culture_id');
    }

    public function intervention(): BelongsTo
    {
        return \->belongsTo(Intervention::class);
    }

    public function mouvementsStock(): HasMany
    {
        return \->hasMany(MouvementStock::class);
    }

    public function lignesVente(): HasMany
    {
        return \->hasMany(LigneVente::class);
    }

    public function scopeDisponibles(Builder \): Builder
    {
        return \
            ->where('statut', StatutLotStock::Disponible->value)
            ->where('quantite_restante', '>', 0);
    }

    public function scopePerimes(Builder \): Builder
    {
        return \->whereNotNull('date_peremption')->whereDate('date_peremption', '<', now());
    }

    public function estDisponible(): bool
    {
        return \->statut === StatutLotStock::Disponible && (float) \->quantite_restante > 0;
    }
}
