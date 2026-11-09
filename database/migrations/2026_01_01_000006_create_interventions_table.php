<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interventions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exploitation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parcelle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cycle_culture_id')->nullable()->constrained('cycles_culturaux')->nullOnDelete();
            $table->foreignId('intrant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('operateur_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 30);
            $table->date('date_intervention');
            $table->decimal('quantite', 12, 2)->nullable();
            $table->string('unite', 20)->nullable();
            $table->decimal('cout', 12, 2)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['exploitation_id', 'date_intervention']);
            $table->index(['cycle_culture_id', 'type']);
            $table->index(['type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interventions');
    }
};
