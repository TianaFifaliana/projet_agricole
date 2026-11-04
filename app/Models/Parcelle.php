<?php

namespace App\Models;

use App\Models\Concerns\BelongsToExploitation;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'exploitation_id',
    'code',
    'nom',
    'superficie_ha',
    'type_sol',
    'latitude',
    'longitude',
    'actif',
])]
class Parcelle extends Model
{
    use BelongsToExploitation, HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'actif' => 'boolean',
            'superficie_ha' => 'decimal:2',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function exploitation(): BelongsTo
    {
        return \->belongsTo(Exploitation::class);
    }

    public function cyclesCulturaux(): HasMany
    {
        return \->hasMany(CycleCulture::class);
    }

    public function interventions(): HasMany
    {
        return \->hasMany(Intervention::class);
    }

    public function lotsStock(): HasMany
    {
        return \->hasMany(LotStock::class);
    }

    public function scopeActives(\)
    {
        return \->where('actif', true);
    }
}
