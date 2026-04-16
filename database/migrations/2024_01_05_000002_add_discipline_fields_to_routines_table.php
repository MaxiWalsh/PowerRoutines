<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('routines', function (Blueprint $table) {
            $table->string('discipline')->nullable()->after('cover_image');         // gym, football, athletics...
            $table->json('target_goals')->nullable()->after('discipline');           // ['strength', 'endurance']
            $table->string('target_level')->nullable()->after('target_goals');      // beginner, intermediate, advanced
            $table->json('contraindications')->nullable()->after('target_level');   // ['knee_injury', 'heart_condition']
        });
    }

    public function down(): void
    {
        Schema::table('routines', function (Blueprint $table) {
            $table->dropColumn(['discipline', 'target_goals', 'target_level', 'contraindications']);
        });
    }
};
