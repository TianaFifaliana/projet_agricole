<?php

namespace Database\Factories;

use App\Enums\PhaseCulturale;
use App\Enums\StatutReleveEau;
use App\Models\CycleCulture;
use App\Models\Exploitation;
use App\Models\Parcelle;
use App\Models\ReleveNiveauEau;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReleveNiveauEau>
 */
class ReleveNiveauEauFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'exploitation_id' => Exploitation::factory(),
            'campaign_id' => CycleCulture::factory(),
            'parcelle_id' => Parcelle::factory(),
            'user_id' => User::factory(),
            'date_releve' => now()->toDateString(),
            'niveau_constate_mm' => fake()->randomFloat(2, 20, 120),
            'precipitations_mm' => 0,
            'phase_culturale' => PhaseCulturale::Vegetatif,
            'niveau_precedent_mm' => null,
            'apport_irrigation_m3' => 0,
            'evaporation_attendue_mm' => 0,
            'percolation_attendue_mm' => 0,
            'niveau_attendu_mm' => null,
            'ecart_mm' => null,
            'pertes_anormales_m3' => null,
            'statut' => StatutReleveEau::Conforme,
            'observation' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (ReleveNiveauEau $releve): void {
            $campaign = CycleCulture::withoutGlobalScopes()->find($releve->campaign_id);

            if ($campaign !== null) {
                $releve->exploitation_id = $campaign->exploitation_id;
                $releve->parcelle_id = $campaign->parcelle_id;
            }
        });
    }
}
