<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_water_budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exploitation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campaign_id')->constrained('cycles_culturaux')->cascadeOnDelete()->unique();
            $table->decimal('volume_alloue_m3', 12, 2);
            $table->decimal('volume_consomme_m3', 12, 2)->default(0);
            $table->decimal('seuil_alerte_pct', 5, 2)->default(80);
            $table->string('statut', 20)->default('actif');
            $table->timestamps();

            $table->index(['exploitation_id', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_water_budgets');
    }
};
