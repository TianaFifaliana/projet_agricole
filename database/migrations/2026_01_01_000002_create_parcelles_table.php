<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parcelles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exploitation_id')->constrained()->cascadeOnDelete();
            $table->string('code', 30);
            $table->string('nom');
            $table->decimal('superficie_ha', 8, 2);
            $table->string('type_sol', 60)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['exploitation_id', 'code']);
            $table->index(['exploitation_id', 'actif']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parcelles');
    }
};
