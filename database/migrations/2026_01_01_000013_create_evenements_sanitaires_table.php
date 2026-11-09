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
            $table->foreignId('intervention_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 30);
            $table->date('date_evenement');
            $table->string('diagnostic')->nullable();
            $table->text('traitement')->nullable();
            $table->string('medicament')->nullable();
            $table->decimal('cout', 12, 2)->nullable();
            $table->date('date_rappel')->nullable();
            $table->text('observations')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['exploitation_id', 'date_evenement']);
            $table->index(['animal_id', 'date_evenement']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evenements_sanitaires');
    }
};
