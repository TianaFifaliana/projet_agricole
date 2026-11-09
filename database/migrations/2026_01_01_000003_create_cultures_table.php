<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cultures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exploitation_id')->constrained()->cascadeOnDelete();
            $table->string('nom', 100);
            $table->string('variete', 100)->nullable();
            $table->unsignedSmallInteger('duree_cycle_jours')->default(90);
            $table->decimal('rendement_cible_tha', 8, 2)->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['exploitation_id', 'nom']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cultures');
    }
};
