<?php

namespace App\Models;

use App\Models\Concerns\BelongsToExploitation;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'exploitation_id',
    'animal_id',
    'type',
    'date_evenement',
    'produit',
    'dose',
    'veterinaire',
    'date_fin_carence',
    'cout',
    'notes',
])]
class EvenementSanitaire extends Model
{
    use BelongsToExploitation, HasFactory;

    protected $table = 'evenements_sanitaires';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_evenement' => 'date',
            'date_fin_carence' => 'date',
            'cout' => 'decimal:2',
        ];
    }

    public function exploitation(): BelongsTo
    {
        return $this->belongsTo(Exploitation::class);
    }

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class);
    }
}
