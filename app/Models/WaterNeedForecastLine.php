<?php

namespace App\Models;

use App\Enums\PhaseCulturale;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'water_need_forecast_id',
    'phase',
    'date_debut',
    'date_fin',
    'duree_jours',
    'besoin_journalier_mm',
    'besoin_net_m3',
    'besoin_brut_m3',
])]
class WaterNeedForecastLine extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'phase' => PhaseCulturale::class,
            'date_debut' => 'date',
            'date_fin' => 'date',
            'duree_jours' => 'integer',
            'besoin_journalier_mm' => 'decimal:2',
            'besoin_net_m3' => 'decimal:2',
            'besoin_brut_m3' => 'decimal:2',
        ];
    }

    public function forecast(): BelongsTo
    {
        return $this->belongsTo(WaterNeedForecast::class, 'water_need_forecast_id');
    }
}
