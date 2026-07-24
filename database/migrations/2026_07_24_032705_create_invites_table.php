<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invites', function (Blueprint $table) {
            $table->id();
            // Bound to a specific email — registration must match this address.
            $table->string('email');
            // SHA-256 hash of the raw token; the raw value is never stored.
            $table->string('token_hash', 64)->unique();
            $table->timestamp('expires_at')->nullable();
            // Single-use: set once when the invite is redeemed.
            $table->timestamp('used_at')->nullable();
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invites');
    }
};
