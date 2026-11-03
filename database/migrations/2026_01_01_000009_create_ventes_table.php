<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ventes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exploitation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('partenaire_id')->constrained('partenaires')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('numero', 30);
            $table->date('date_vente');
            $table->string('statut', 20)->default('brouillon');
            $table->decimal('total_ht', 14, 2)->default(0);
            $table->decimal('tva', 14, 2)->default(0);
            $table->decimal('total_ttc', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('validee_at')->nullable();
            $table->timestamps();

            $table->unique(['exploitation_id', 'numero']);
            $table->index(['exploitation_id', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ventes');
    }
};
