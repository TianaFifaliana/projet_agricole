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
    'type',
    'nom',
    'prenom',
    'raison_sociale',
    'email',
    'telephone',
    'adresse',
    'ville',
    'code_postal',
    'siret',
    'actif',
])]
class Partenaire extends Model
{
    use BelongsToExploitation, HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'actif' => 'boolean',
        ];
    }

    public function exploitation(): BelongsTo
    {
        return \->belongsTo(Exploitation::class);
    }

    public function ventes(): HasMany
    {
        return \->hasMany(Vente::class);
    }

    public function scopeActifs(Builder \): Builder
    {
        return \->where('actif', true);
    }

    public function scopeClients(Builder \): Builder
    {
        return \->where('type', 'client');
    }

    public function scopeFournisseurs(Builder \): Builder
    {
        return \->where('type', 'fournisseur');
    }
}
