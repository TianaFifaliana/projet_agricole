<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            ExploitationSeeder::class,
            ReferentielSeeder::class,
            ActiviteSeeder::class,
            ElevageSeeder::class,
            IrrigationSeeder::class,
        ]);
    }
}
