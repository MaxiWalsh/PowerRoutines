<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exercise_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exercise_id')->constrained()->cascadeOnDelete();
            $table->foreignId('routine_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('block_id')->nullable()->constrained()->nullOnDelete();
            $table->float('weight_kg');
            $table->integer('reps');
            $table->integer('sets');
            $table->text('notes')->nullable();
            $table->timestamp('logged_at'); // fecha real del entrenamiento
            $table->timestamps();

            // Índices para consultas frecuentes de progreso
            $table->index(['user_id', 'exercise_id', 'logged_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exercise_logs');
    }
};
