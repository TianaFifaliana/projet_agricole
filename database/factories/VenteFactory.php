<?php

namespace Database\Factories;

use App\Enums\StatutVente;
use App\Models\Exploitation;
use App\Models\Partenaire;
use App\Models\User;
use App\Models\Vente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vente>
 */
class VenteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $date = fake()->dateTimeBetween('-3 months', 'now');

        return [
            'exploitation_id' => Exploitation::factory(),
            'partenaire_id' => Partenaire::factory(),
            'user_id' => User::factory(),
            'numero' => 'VTE-'.$date->format('Y').'-'.fake()->unique()->numerify('####'),
            'date_vente' => $date,
            'statut' => StatutVente::Brouillon,
            'total_ht' => 0,
            'tva' => 0,
            'total_ttc' => 0,
            'notes' => fake()->optional()->sentence(),
            'validee_at' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (Vente $vente): void {
            $partenaire = Partenaire::withoutGlobalScopes()->find($vente->partenaire_id);

            if ($partenaire !== null) {
                $vente->exploitation_id = $partenaire->exploitation_id;
            }
        });
    }

    public function validee(): static
    {
        return $this->state(fn (array $attributes): array => [
            'statut' => StatutVente::Validee,
            'validee_at' => now(),
        ]);
    }
}
