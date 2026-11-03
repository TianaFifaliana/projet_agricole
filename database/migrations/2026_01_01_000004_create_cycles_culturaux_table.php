<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cycles_culturaux', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exploitation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parcelle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('culture_id')->constrained()->restrictOnDelete();
            $table->string('campagne', 9);
            $table->date('date_semis');
            $table->date('date_recolte_prevue')->nullable();
            $table->date('date_recolte_reelle')->nullable();
            $table->string('statut', 20)->default('planifie');
            $table->decimal('surface_ha', 8, 2);
            $table->decimal('rendement_obtenu_tha', 8, 2)->nullable();
            $table->decimal('quantite_recoltee_kg', 12, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['exploitation_id', 'campagne']);
            $table->index(['parcelle_id', 'campagne']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cycles_culturaux');
    }
};
