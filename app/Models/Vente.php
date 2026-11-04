<?php

namespace App\Models;

use App\Enums\StatutVente;
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
    'partenaire_id',
    'user_id',
    'numero',
    'date_vente',
    'statut',
    'total_ht',
    'tva',
    'total_ttc',
    'taux_tva',
    'notes',
    'validee_at',
])]
class Vente extends Model
{
    use BelongsToExploitation, HasFactory, SoftDeletes;

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
            'taux_tva' => 'decimal:2',
            'validee_at' => 'datetime',
        ];
    }

    public function exploitation(): BelongsTo
    {
        return \->belongsTo(Exploitation::class);
    }

    public function partenaire(): BelongsTo
    {
        return \->belongsTo(Partenaire::class);
    }

    public function user(): BelongsTo
    {
        return \->belongsTo(User::class);
    }

    public function lignes(): HasMany
    {
        return \->hasMany(LigneVente::class);
    }

    public function mouvementsStock(): HasMany
    {
        return \->hasMany(MouvementStock::class);
    }

    public function scopeBrouillons(Builder \): Builder
    {
        return \->where('statut', StatutVente::Brouillon->value);
    }

    public function scopeValidees(Builder \): Builder
    {
        return \->where('statut', StatutVente::Validee->value);
    }

    public function estBrouillon(): bool
    {
        return \->statut === StatutVente::Brouillon;
    }

    public function estValidee(): bool
    {
        return \->statut === StatutVente::Validee;
    }
}
