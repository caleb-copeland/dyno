<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workouts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('focus_area');            // App\Enums\FocusArea
            $table->unsignedSmallInteger('estimated_minutes')->nullable();
            $table->string('level')->nullable();     // App\Enums\Level
            $table->text('notes')->nullable();
            // Curator can retire a workout without breaking historical logs.
            $table->boolean('is_published')->default(true);
            $table->timestamps();

            $table->index('focus_area');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workouts');
    }
};
