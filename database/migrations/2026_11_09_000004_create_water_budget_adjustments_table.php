<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('water_budget_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exploitation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campaign_water_budget_id')->constrained('campaign_water_budgets')->cascadeOnDelete();
            $table->foreignId('demande_par_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('valide_par_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('volume_supplementaire_m3', 12, 2);
            $table->text('motif');
            $table->string('statut', 20)->default('en_attente');
            $table->timestamp('date_decision')->nullable();
            $table->text('commentaire_decision')->nullable();
            $table->timestamps();

            $table->index(['exploitation_id', 'statut']);
            $table->index(['campaign_water_budget_id', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('water_budget_adjustments');
    }
};
