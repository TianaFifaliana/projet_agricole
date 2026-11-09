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
    'identifiant',
    'nom',
    'espece',
    'race',
    'sexe',
    'date_naissance',
    'poids_kg',
    'statut',
    'date_entree',
    'date_sortie',
])]
class Animal extends Model
{
    use BelongsToExploitation, HasFactory, SoftDeletes;

    protected $table = 'animaux';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_naissance' => 'date',
            'date_entree' => 'date',
            'date_sortie' => 'date',
            'poids_kg' => 'decimal:2',
        ];
    }

    public function exploitation(): BelongsTo
    {
        return $this->belongsTo(Exploitation::class);
    }

    public function evenementsSanitaires(): HasMany
    {
        return $this->hasMany(EvenementSanitaire::class);
    }

    public function scopeActifs(Builder $query): Builder
    {
        return $query->where('statut', 'actif');
    }
}
