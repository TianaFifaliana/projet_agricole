<?php

namespace App\Models;

use App\Models\Concerns\BelongsToExploitation;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'exploitation_id',
    'animal_id',
    'intervention_id',
    'type',
    'date_evenement',
    'diagnostic',
    'traitement',
    'medicament',
    'cout',
    'date_rappel',
    'observations',
])]
class EvenementSanitaire extends Model
{
    use BelongsToExploitation, HasFactory, SoftDeletes;

    protected \ = 'evenements_sanitaires';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_evenement' => 'date',
            'date_rappel' => 'date',
            'cout' => 'decimal:2',
        ];
    }

    public function exploitation(): BelongsTo
    {
        return \->belongsTo(Exploitation::class);
    }

    public function animal(): BelongsTo
    {
        return \->belongsTo(Animal::class);
    }

    public function intervention(): BelongsTo
    {
        return \->belongsTo(Intervention::class);
    }

    public function scopeAvenir(Builder \): Builder
    {
        return \->whereNotNull('date_rappel')->whereDate('date_rappel', '>=', now());
    }

    public function scopeParPeriode(Builder \, \, \): Builder
    {
        return \->whereBetween('date_evenement', [\, \]);
    }
}
