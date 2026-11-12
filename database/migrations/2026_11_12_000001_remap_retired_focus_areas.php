<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * FocusArea dropped back/arms/push (back was renamed pullups). Rows still
 * holding a retired value fail the enum cast on read, so remap or remove them.
 */
return new class extends Migration
{
    private const TABLES = ['exercises', 'workouts', 'scheduled_sessions', 'test_results'];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            DB::table($table)->where('focus_area', 'back')->update(['focus_area' => 'pullups']);
        }

        // Arms/push have no successor. Only schedule slots are safe to drop;
        // library rows and test results would need an admin's call.
        DB::table('scheduled_sessions')->whereIn('focus_area', ['arms', 'push'])->delete();
    }

    public function down(): void
    {
        // Data remap — not reversible.
    }
};
