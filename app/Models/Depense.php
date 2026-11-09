<?php

namespace App\Models;

use App\Enums\CategorieDepense;
use App\Models\Concerns\BelongsToExploitation;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'exploitation_id',
    'irrigation_id',
    'user_id',
    'date',
    'categorie',
    'libelle',
    'montant',
    'notes',
])]
class Depense extends Model
{
    use BelongsToExploitation, HasFactory, SoftDeletes;

    protected $table = 'depenses';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'categorie' => CategorieDepense::class,
            'montant' => 'decimal:2',
        ];
    }

    public function exploitation(): BelongsTo
    {
        return $this->belongsTo(Exploitation::class);
    }

    public function irrigation(): BelongsTo
    {
        return $this->belongsTo(Irrigation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeParCategorie(Builder $query, CategorieDepense $categorie): Builder
    {
        return $query->where('categorie', $categorie->value);
    }

    public function scopeParPeriode(Builder $query, $debut, $fin): Builder
    {
        return $query->whereBetween('date', [$debut, $fin]);
    }
}
