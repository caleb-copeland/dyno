<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('focus_area');   // App\Enums\FocusArea
            $table->string('metric');       // e.g. added_weight, one_rep_max — App\Support\BaselineTests key
            $table->decimal('value', 7, 2);
            $table->string('unit', 16)->default('kg');
            $table->timestamp('tested_at');
            $table->timestamps();

            $table->index(['user_id', 'metric', 'tested_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_results');
    }
};
