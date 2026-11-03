<?php

namespace App\Models;

use App\Models\Concerns\BelongsToExploitation;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
    use BelongsToExploitation, HasFactory;

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
        return $this->belongsTo(Exploitation::class);
    }

    public function interventions(): HasMany
    {
        return $this->hasMany(Intervention::class);
    }

    public function lotsStock(): HasMany
    {
        return $this->hasMany(LotStock::class);
    }

    public function quantiteDisponible(): float
    {
        return (float) $this->lotsStock()
            ->where('statut', 'disponible')
            ->sum('quantite_restante');
    }
}
