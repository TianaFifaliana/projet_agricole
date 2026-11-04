<?php

namespace App\Models;

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
    'nom',
    'categorie',
    'unite',
    'seuil_alerte',
    'prix_unitaire',
    'actif',
])]
class Intrant extends Model
{
    use BelongsToExploitation, HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'actif' => 'boolean',
            'seuil_alerte' => 'decimal:2',
            'prix_unitaire' => 'decimal:2',
        ];
    }

    public function exploitation(): BelongsTo
    {
        return \->belongsTo(Exploitation::class);
    }

    public function lotsStock(): HasMany
    {
        return \->hasMany(LotStock::class);
    }

    public function interventions(): HasMany
    {
        return \->hasMany(Intervention::class);
    }

    public function scopeActifs(Builder \): Builder
    {
        return \->where('actif', true);
    }

    public function scopeParCategorie(Builder \, string \): Builder
    {
        return \->where('categorie', \);
    }
}
