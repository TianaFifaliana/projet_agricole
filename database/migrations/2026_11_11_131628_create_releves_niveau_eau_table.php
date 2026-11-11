<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('releves_niveau_eau', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exploitation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campaign_id')->constrained('cycles_culturaux')->cascadeOnDelete();
            $table->foreignId('parcelle_id')->constrained('parcelles')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('date_releve');
            $table->decimal('niveau_constate_mm', 6, 2);
            $table->decimal('precipitations_mm', 6, 2)->default(0);
            $table->string('phase_culturale');
            $table->decimal('niveau_precedent_mm', 6, 2)->nullable();
            $table->decimal('apport_irrigation_m3', 12, 2)->default(0);
            $table->decimal('evaporation_attendue_mm', 6, 2)->default(0);
            $table->decimal('percolation_attendue_mm', 6, 2)->default(0);
            $table->decimal('niveau_attendu_mm', 6, 2)->nullable();
            $table->decimal('ecart_mm', 6, 2)->nullable();
            $table->decimal('pertes_anormales_m3', 12, 2)->nullable();
            $table->string('statut')->default('conforme');
            $table->text('observation')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['exploitation_id', 'date_releve']);
            $table->index(['campaign_id', 'date_releve']);
            $table->index(['parcelle_id', 'date_releve']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('releves_niveau_eau');
    }
};
