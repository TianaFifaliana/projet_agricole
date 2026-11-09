<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('depenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exploitation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('irrigation_id')->nullable()->constrained('irrigations')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('date');
            $table->string('categorie', 30);
            $table->string('libelle', 180);
            $table->decimal('montant', 12, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['exploitation_id', 'date']);
            $table->index(['exploitation_id', 'categorie']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('depenses');
    }
};
