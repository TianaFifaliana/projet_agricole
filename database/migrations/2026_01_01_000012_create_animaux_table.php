<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('animaux', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exploitation_id')->constrained()->cascadeOnDelete();
            $table->string('identifiant', 50);
            $table->string('nom', 100)->nullable();
            $table->string('espece', 50);
            $table->string('race', 50)->nullable();
            $table->string('sexe', 10);
            $table->date('date_naissance')->nullable();
            $table->decimal('poids_kg', 8, 2)->nullable();
            $table->string('statut', 20)->default('actif');
            $table->date('date_entree');
            $table->date('date_sortie')->nullable();
            $table->timestamps();

            $table->unique(['exploitation_id', 'identifiant']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animaux');
    }
};
