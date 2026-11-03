<?php

namespace App\Models;

use App\Enums\StatutVente;
use App\Models\Concerns\BelongsToExploitation;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'exploitation_id',
    'partenaire_id',
    'user_id',
    'numero',
    'date_vente',
    'statut',
    'total_ht',
    'tva',
    'total_ttc',
    'notes',
    'validee_at',
])]
class Vente extends Model
{
    use BelongsToExploitation, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_vente' => 'date',
            'statut' => StatutVente::class,
            'total_ht' => 'decimal:2',
            'tva' => 'decimal:2',
            'total_ttc' => 'decimal:2',
            'validee_at' => 'datetime',
        ];
    }

    public function exploitation(): BelongsTo
    {
        return $this->belongsTo(Exploitation::class);
    }

    public function partenaire(): BelongsTo
    {
        return $this->belongsTo(Partenaire::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(LigneVente::class);
    }

    public function mouvementsStock(): HasMany
    {
        return $this->hasMany(MouvementStock::class);
    }

    public function estBrouillon(): bool
    {
        return $this->statut === StatutVente::Brouillon;
    }
}
