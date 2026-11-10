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
        Schema::create('water_need_forecast_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('water_need_forecast_id')->constrained()->cascadeOnDelete();
            $table->string('phase', 20);
            $table->date('date_debut');
            $table->date('date_fin');
            $table->unsignedSmallInteger('duree_jours');
            $table->decimal('besoin_journalier_mm', 6, 2);
            $table->decimal('besoin_net_m3', 14, 2);
            $table->decimal('besoin_brut_m3', 14, 2);
            $table->timestamps();

            $table->unique(['water_need_forecast_id', 'phase']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('water_need_forecast_lines');
    }
};
