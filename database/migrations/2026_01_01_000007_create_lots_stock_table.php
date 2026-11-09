<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lots_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exploitation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('intrant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('parcelle_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cycle_culture_id')->nullable()->constrained('cycles_culturaux')->nullOnDelete();
            $table->foreignId('intervention_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code_lot', 50);
            $table->decimal('quantite_initiale', 12, 2);
            $table->decimal('quantite_restante', 12, 2);
            $table->string('unite', 20);
            $table->date('date_entree');
            $table->date('date_peremption')->nullable();
            $table->string('origine', 50)->nullable();
            $table->decimal('prix_unitaire', 12, 2)->nullable();
            $table->string('statut', 20)->default('disponible');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['exploitation_id', 'code_lot']);
            $table->index(['exploitation_id', 'statut']);
            $table->index(['intrant_id', 'statut']);
            $table->index(['date_peremption']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lots_stock');
    }
};
