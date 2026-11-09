<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mouvements_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exploitation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lot_stock_id')->constrained('lots_stock')->restrictOnDelete();
            $table->foreignId('vente_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('intervention_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 20);
            $table->decimal('quantite', 12, 2);
            $table->decimal('quantite_avant', 12, 2);
            $table->decimal('quantite_apres', 12, 2);
            $table->string('motif', 100);
            $table->string('reference', 100)->nullable();
            $table->dateTime('date_mouvement');
            $table->timestamps();

            $table->index(['exploitation_id', 'date_mouvement']);
            $table->index(['lot_stock_id', 'date_mouvement']);
            $table->index(['type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mouvements_stock');
    }
};
