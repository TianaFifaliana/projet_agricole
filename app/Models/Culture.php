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
    'variete',
    'duree_cycle_jours',
    'rendement_cible_tha',
    'actif',
])]
class Culture extends Model
{
    use BelongsToExploitation, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'actif' => 'boolean',
            'duree_cycle_jours' => 'integer',
            'rendement_cible_tha' => 'decimal:2',
        ];
    }

    public function exploitation(): BelongsTo
    {
        return $this->belongsTo(Exploitation::class);
    }

    public function cyclesCulturaux(): HasMany
    {
        return $this->hasMany(CycleCulture::class);
    }
}
