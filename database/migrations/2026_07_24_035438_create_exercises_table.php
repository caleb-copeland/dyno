<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exercises', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('focus_area');           // App\Enums\FocusArea
            $table->string('prescription_type');    // App\Enums\PrescriptionType
            $table->text('instructions')->nullable();
            $table->string('media_url')->nullable();
            // The scheduler's hard rules read THIS, not the focus area — some
            // back/arms work loads fingers heavily and some doesn't (§4).
            $table->boolean('is_finger_intensive')->default(false);
            $table->timestamps();

            $table->index('focus_area');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exercises');
    }
};
