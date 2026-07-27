<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The schedule is now built by hand (workouts placed directly on days), so the
 * old generator inputs are dead. Days and workouts live on scheduled_sessions.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropColumn(['training_days', 'climbing_days', 'frequencies']);
        });
    }

    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->json('training_days')->nullable();
            $table->json('climbing_days')->nullable();
            $table->json('frequencies')->nullable();
        });
    }
};
