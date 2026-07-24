<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('set_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workout_log_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exercise_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('set_number');
            $table->unsignedSmallInteger('reps')->nullable();
            $table->decimal('weight', 6, 2)->nullable();
            $table->unsignedInteger('duration_s')->nullable();
            $table->boolean('completed')->default(false);
            $table->timestamps();

            // One row per (log, exercise, set) — lets the runner upsert idempotently.
            $table->unique(['workout_log_id', 'exercise_id', 'set_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('set_logs');
    }
};
