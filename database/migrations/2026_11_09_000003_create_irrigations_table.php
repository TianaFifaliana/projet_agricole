<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('irrigations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exploitation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campaign_id')->constrained('cycles_culturaux')->cascadeOnDelete();
            $table->foreignId('production_unit_id')->constrained('parcelles')->cascadeOnDelete();
            $table->foreignId('water_source_id')->constrained('water_sources')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('date');
            $table->unsignedInteger('duree_minutes');
            $table->decimal('debit_m3_h', 10, 2);
            $table->decimal('volume_m3', 12, 2);
            $table->decimal('cout', 12, 2)->nullable();
            $table->text('observation')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['exploitation_id', 'date']);
            $table->index(['campaign_id', 'date']);
            $table->index(['production_unit_id', 'date']);
            $table->index(['water_source_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('irrigations');
    }
};
