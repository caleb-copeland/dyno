<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ordered join: the exercises that make up a curated workout template.
        Schema::create('workout_exercises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workout_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exercise_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position')->default(0);
            $table->unsignedSmallInteger('sets')->default(1);
            $table->unsignedSmallInteger('target_reps')->nullable();
            $table->unsignedInteger('target_duration_s')->nullable();
            $table->unsignedInteger('rest_s')->nullable();
            // For interval (hangboard) prescriptions — the on/off work pattern.
            $table->unsignedSmallInteger('interval_work_s')->nullable();
            $table->unsignedSmallInteger('interval_rest_s')->nullable();
            $table->unsignedSmallInteger('interval_reps')->nullable();
            $table->string('prescription_basis')->default('fixed'); // App\Enums\PrescriptionBasis
            // When basis = percent_of_test, the fraction (e.g. 0.80) of tested max.
            $table->decimal('percent_of_test', 4, 2)->nullable();
            $table->timestamps();

            $table->index(['workout_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workout_exercises');
    }
};
