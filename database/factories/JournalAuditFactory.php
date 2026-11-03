<?php

namespace Database\Factories;

use App\Models\Exploitation;
use App\Models\JournalAudit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JournalAudit>
 */
class JournalAuditFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'exploitation_id' => Exploitation::factory(),
            'user_id' => User::factory(),
            'action' => fake()->randomElement(['recolte', 'validation_vente', 'annulation_vente', 'creation']),
            'entite' => 'App\\Models\\CycleCulture',
            'entite_id' => fake()->numberBetween(1, 100),
            'anciennes_valeurs' => null,
            'nouvelles_valeurs' => ['statut' => 'test'],
            'ip' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'created_at' => now(),
        ];
    }
}
