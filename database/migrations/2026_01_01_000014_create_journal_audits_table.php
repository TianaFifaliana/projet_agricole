<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exploitation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 30);
            $table->string('entite', 60);
            $table->unsignedBigInteger('entite_id')->nullable();
            $table->json('anciennes_valeurs')->nullable();
            $table->json('nouvelles_valeurs')->nullable();
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['entite', 'entite_id']);
            $table->index(['exploitation_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_audits');
    }
};
