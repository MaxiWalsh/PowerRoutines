<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('routines', function (Blueprint $table) {
            $table->decimal('price', 8, 2)->nullable()->after('is_active');
            $table->boolean('is_published')->default(false)->after('price');
            $table->text('marketplace_description')->nullable()->after('is_published');
            $table->string('difficulty')->nullable()->after('marketplace_description'); // beginner|intermediate|advanced
            $table->unsignedTinyInteger('duration_weeks')->nullable()->after('difficulty');
            $table->unsignedTinyInteger('days_per_week')->nullable()->after('duration_weeks');
            $table->string('cover_image')->nullable()->after('days_per_week');
        });
    }

    public function down(): void
    {
        Schema::table('routines', function (Blueprint $table) {
            $table->dropColumn([
                'price', 'is_published', 'marketplace_description',
                'difficulty', 'duration_weeks', 'days_per_week', 'cover_image',
            ]);
        });
    }
};
