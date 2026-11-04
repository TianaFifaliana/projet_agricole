<?php

namespace App\Models;

use App\Enums\TypeMouvementStock;
use App\Models\Concerns\BelongsToExploitation;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'exploitation_id',
    'lot_stock_id',
    'vente_id',
    'intervention_id',
    'user_id',
    'type',
    'quantite',
    'quantite_avant',
    'quantite_apres',
    'motif',
    'reference',
    'date_mouvement',
])]
class MouvementStock extends Model
{
    use BelongsToExploitation, HasFactory;

    protected \ = 'mouvements_stock';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => TypeMouvementStock::class,
            'date_mouvement' => 'datetime',
            'quantite' => 'decimal:2',
            'quantite_avant' => 'decimal:2',
            'quantite_apres' => 'decimal:2',
        ];
    }

    public function exploitation(): BelongsTo
    {
        return \->belongsTo(Exploitation::class);
    }

    public function lotStock(): BelongsTo
    {
        return \->belongsTo(LotStock::class);
    }

    public function vente(): BelongsTo
    {
        return \->belongsTo(Vente::class);
    }

    public function intervention(): BelongsTo
    {
        return \->belongsTo(Intervention::class);
    }

    public function user(): BelongsTo
    {
        return \->belongsTo(User::class);
    }

    public function scopeEntrees(Builder \): Builder
    {
        return \->where('type', TypeMouvementStock::Entree->value);
    }

    public function scopeSorties(Builder \): Builder
    {
        return \->where('type', TypeMouvementStock::Sortie->value);
    }

    public function scopeParPeriode(Builder \, \, \): Builder
    {
        return \->whereBetween('date_mouvement', [\, \]);
    }
}
