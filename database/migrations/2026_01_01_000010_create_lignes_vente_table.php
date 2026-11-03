<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lignes_vente', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vente_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lot_stock_id')->nullable()->constrained('lots_stock')->nullOnDelete();
            $table->string('designation', 150);
            $table->decimal('quantite', 12, 2);
            $table->string('unite', 20);
            $table->decimal('prix_unitaire', 12, 2);
            $table->decimal('montant', 14, 2);
            $table->timestamps();

            $table->index('lot_stock_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lignes_vente');
    }
};
