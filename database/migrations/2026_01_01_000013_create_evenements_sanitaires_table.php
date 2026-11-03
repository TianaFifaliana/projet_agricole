<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evenements_sanitaires', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exploitation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('animal_id')->constrained('animaux')->cascadeOnDelete();
            $table->string('type', 30);
            $table->date('date_evenement');
            $table->string('produit', 100)->nullable();
            $table->string('dose', 50)->nullable();
            $table->string('veterinaire', 100)->nullable();
            $table->date('date_fin_carence')->nullable();
            $table->decimal('cout', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['exploitation_id', 'date_evenement']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evenements_sanitaires');
    }
};
