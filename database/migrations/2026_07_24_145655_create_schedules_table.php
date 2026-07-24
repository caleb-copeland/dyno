<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('active')->default(true);
            // 0 = Monday … 6 = Sunday.
            $table->unsignedTinyInteger('week_start_day')->default(0);
            // Generator inputs, kept so the week can be re-generated / re-shuffled.
            $table->json('training_days')->nullable();   // [int day]
            $table->json('climbing_days')->nullable();    // [int day] — first-class, finger-maximal
            $table->json('frequencies')->nullable();      // { focus_area: sessions_per_week }
            $table->timestamps();

            $table->index(['user_id', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
