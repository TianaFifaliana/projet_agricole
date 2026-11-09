<?php

namespace Database\Factories;

use App\Enums\EtatSourceEau;
use App\Enums\TypeSourceEau;
use App\Models\Exploitation;
use App\Models\WaterSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WaterSource>
 */
class WaterSourceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'exploitation_id' => Exploitation::factory(),
            'nom' => 'Source '.fake()->unique()->word(),
            'type' => fake()->randomElement(TypeSourceEau::cases()),
            'debit_m3_h' => fake()->randomFloat(2, 5, 200),
            'etat' => EtatSourceEau::Operationnelle,
            'actif' => true,
        ];
    }

    public function enPanne(): static
    {
        return $this->state(fn (array $attributes): array => [
            'etat' => EtatSourceEau::EnPanne,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'actif' => false,
        ]);
    }
}
