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
            $table->string('type', 20);
            $table->string('nom');
            $table->string('prenom')->nullable();
            $table->string('raison_sociale')->nullable();
            $table->string('email')->nullable();
            $table->string('telephone', 30)->nullable();
            $table->string('adresse')->nullable();
            $table->string('ville', 100)->nullable();
            $table->string('code_postal', 20)->nullable();
            $table->string('siret', 20)->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['exploitation_id', 'type']);
            $table->index(['actif']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partenaires');
    }
};
