<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partenaires', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exploitation_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20)->default('client');
            $table->string('nom');
            $table->string('email')->nullable();
            $table->string('telephone', 30)->nullable();
            $table->string('adresse')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();

            $table->index(['exploitation_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partenaires');
    }
};
