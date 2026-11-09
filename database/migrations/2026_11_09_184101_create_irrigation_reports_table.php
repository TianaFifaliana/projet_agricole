<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('irrigation_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exploitation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campaign_id')->constrained('cycles_culturaux')->cascadeOnDelete()->unique();
            $table->date('periode_debut')->nullable();
            $table->date('periode_fin')->nullable();
            $table->decimal('total_volume_m3', 12, 2)->default(0);
            $table->json('lignes')->nullable();
            $table->timestamp('genere_le')->nullable();
            $table->timestamps();

            $table->index(['exploitation_id', 'genere_le']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('irrigation_reports');
    }
};
