<?php

namespace App\Models;

use App\Models\Concerns\BelongsToExploitation;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'exploitation_id',
    'campaign_id',
    'production_unit_id',
    'water_source_id',
    'user_id',
    'date',
    'duree_minutes',
    'debit_m3_h',
    'volume_m3',
    'cout',
    'observation',
])]
class Irrigation extends Model
{
    use BelongsToExploitation, HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'duree_minutes' => 'integer',
            'debit_m3_h' => 'decimal:2',
            'volume_m3' => 'decimal:2',
            'cout' => 'decimal:2',
        ];
    }

    public function exploitation(): BelongsTo
    {
        return $this->belongsTo(Exploitation::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(CycleCulture::class, 'campaign_id');
    }

    public function productionUnit(): BelongsTo
    {
        return $this->belongsTo(Parcelle::class, 'production_unit_id');
    }

    public function waterSource(): BelongsTo
    {
        return $this->belongsTo(WaterSource::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function depense(): HasOne
    {
        return $this->hasOne(Depense::class);
    }

    public function scopeParCampagne(Builder $query, int $campaignId): Builder
    {
        return $query->where('campaign_id', $campaignId);
    }

    public function scopeParProductionUnit(Builder $query, int $productionUnitId): Builder
    {
        return $query->where('production_unit_id', $productionUnitId);
    }

    public function scopeParSource(Builder $query, int $waterSourceId): Builder
    {
        return $query->where('water_source_id', $waterSourceId);
    }

    public function scopeParPeriode(Builder $query, $debut, $fin): Builder
    {
        return $query->whereBetween('date', [$debut, $fin]);
    }
}
