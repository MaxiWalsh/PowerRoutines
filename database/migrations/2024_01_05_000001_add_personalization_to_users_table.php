<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('discipline')->nullable()->after('height_cm');    // gym, football, athletics...
            $table->string('goal')->nullable()->after('discipline');         // strength, weight_loss, endurance...
            $table->string('fitness_level')->nullable()->after('goal');      // beginner, intermediate, advanced
            $table->json('conditions')->nullable()->after('fitness_level'); // ['knee_injury', 'hypertension', ...]
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['discipline', 'goal', 'fitness_level', 'conditions']);
        });
    }
};
