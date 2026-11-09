<?php

namespace App\Models;

use App\Models\Concerns\BelongsToExploitation;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'exploitation_id',
    'campaign_id',
    'periode_debut',
    'periode_fin',
    'total_volume_m3',
    'lignes',
    'genere_le',
])]
class IrrigationReport extends Model
{
    use BelongsToExploitation, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'periode_debut' => 'date',
            'periode_fin' => 'date',
            'total_volume_m3' => 'decimal:2',
            'lignes' => 'array',
            'genere_le' => 'datetime',
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
}
