<?php

namespace App\Models;

use App\Enums\EtatSourceEau;
use App\Enums\TypeSourceEau;
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
    'nom',
    'type',
    'debit_m3_h',
    'etat',
    'actif',
])]
class WaterSource extends Model
{
    use BelongsToExploitation, HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => TypeSourceEau::class,
            'etat' => EtatSourceEau::class,
            'debit_m3_h' => 'decimal:2',
            'actif' => 'boolean',
        ];
    }

    public function exploitation(): BelongsTo
    {
        return $this->belongsTo(Exploitation::class);
    }

    public function irrigations(): HasMany
    {
        return $this->hasMany(Irrigation::class);
    }

    public function estOperationnelle(): bool
    {
        return $this->actif && $this->etat->estOperationnelle();
    }

    public function scopeActives(Builder $query): Builder
    {
        return $query->where('actif', true);
    }

    public function scopeOperationnelles(Builder $query): Builder
    {
        return $query->where('etat', EtatSourceEau::Operationnelle->value);
    }
}
