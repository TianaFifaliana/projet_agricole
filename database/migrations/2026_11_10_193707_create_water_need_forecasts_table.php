<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('water_need_forecasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exploitation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campaign_id')->constrained('cycles_culturaux')->cascadeOnDelete()->unique();
            $table->foreignId('campaign_water_budget_id')->nullable()->constrained('campaign_water_budgets')->nullOnDelete();
            $table->decimal('surface_ha', 10, 2);
            $table->unsignedInteger('duree_cycle_jours');
            $table->date('date_semis');
            $table->decimal('rendement_reseau_pct', 5, 2);
            $table->decimal('marge_securite_pct', 5, 2);
            $table->decimal('besoin_net_total_m3', 14, 2);
            $table->decimal('besoin_brut_total_m3', 14, 2);
            $table->decimal('volume_propose_m3', 14, 2);
            $table->string('statut', 20)->default('proposee');
            $table->timestamps();

            $table->index(['exploitation_id', 'statut']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('water_need_forecasts');
    }
};
