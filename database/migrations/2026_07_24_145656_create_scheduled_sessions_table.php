<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheduled_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week'); // 0 = Monday … 6 = Sunday
            $table->string('focus_area');               // App\Enums\FocusArea
            // Which curated workout fills this slot (rotates). Null until picked.
            $table->foreignId('workout_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('position')->default(0);
            $table->timestamps();

            $table->index(['schedule_id', 'day_of_week', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_sessions');
    }
};
