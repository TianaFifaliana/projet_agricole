<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'vente_id',
    'lot_stock_id',
    'designation',
    'quantite',
    'unite',
    'prix_unitaire',
    'montant',
])]
class LigneVente extends Model
{
    use HasFactory;

    protected \ = 'lignes_vente';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantite' => 'decimal:2',
            'prix_unitaire' => 'decimal:2',
            'montant' => 'decimal:2',
        ];
    }

    public function vente(): BelongsTo
    {
        return \->belongsTo(Vente::class);
    }

    public function lotStock(): BelongsTo
    {
        return \->belongsTo(LotStock::class);
    }
}
