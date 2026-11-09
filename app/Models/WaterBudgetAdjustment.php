<?php

namespace App\Models;

use App\Enums\StatutRallonge;
use App\Models\Concerns\BelongsToExploitation;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'exploitation_id',
    'campaign_water_budget_id',
    'demande_par_id',
    'valide_par_id',
    'volume_supplementaire_m3',
    'motif',
    'statut',
    'date_decision',
    'commentaire_decision',
])]
class WaterBudgetAdjustment extends Model
{
    use BelongsToExploitation, HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'volume_supplementaire_m3' => 'decimal:2',
            'statut' => StatutRallonge::class,
            'date_decision' => 'datetime',
        ];
    }

    public function exploitation(): BelongsTo
    {
        return $this->belongsTo(Exploitation::class);
    }

    public function budget(): BelongsTo
    {
        return $this->belongsTo(CampaignWaterBudget::class, 'campaign_water_budget_id');
    }

    public function demandeur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'demande_par_id');
    }

    public function validateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'valide_par_id');
    }

    public function estEnAttente(): bool
    {
        return $this->statut === StatutRallonge::EnAttente;
    }
}
