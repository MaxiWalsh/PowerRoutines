<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Perfiles de entrenamiento (General, Futbolista, Ciclista, etc.)
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gym_id')->nullable()->constrained()->nullOnDelete(); // null = global
            $table->string('name');                 // ej: "Futbolista", "General"
            $table->text('description')->nullable();
            $table->boolean('is_global')->default(false); // disponible para todos los gyms
            $table->timestamps();
        });

        // Tabla pivot: qué perfiles tiene asignados cada user
        Schema::create('profile_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['profile_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_user');
        Schema::dropIfExists('profiles');
    }
};
