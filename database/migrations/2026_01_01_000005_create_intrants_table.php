<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intrants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exploitation_id')->constrained()->cascadeOnDelete();
            $table->string('nom', 100);
            $table->string('categorie', 30);
            $table->string('unite', 20);
            $table->decimal('seuil_alerte', 12, 2)->default(0);
            $table->decimal('prix_unitaire', 12, 2)->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();

            $table->unique(['exploitation_id', 'nom']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intrants');
    }
};
