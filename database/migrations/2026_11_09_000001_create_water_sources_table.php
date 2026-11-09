<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('water_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exploitation_id')->constrained()->cascadeOnDelete();
            $table->string('nom', 120);
            $table->string('type', 20);
            $table->decimal('debit_m3_h', 10, 2)->default(0);
            $table->string('etat', 20)->default('operationnelle');
            $table->boolean('actif')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['exploitation_id', 'etat']);
            $table->index(['exploitation_id', 'actif']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('water_sources');
    }
};
