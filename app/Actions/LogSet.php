<?php

namespace App\Actions;

use App\Models\SetLog;
use App\Models\User;
use App\Models\Workout;
use App\Models\WorkoutLog;

/**
 * Records a single set completion. Shared by the Livewire runner (online) and
 * the JSON endpoint that the offline queue flushes to — so both paths enforce
 * the same rules: workout must be published, the row must belong to THIS
 * workout, the set number must be in range, and everything is scoped to the
 * authenticated user (never trusting a client-supplied exercise id).
 */
class LogSet
{
    public function handle(User $user, Workout $workout, int $workoutExerciseId, int $setNumber, bool $completed): SetLog
    {
        abort_unless($workout->is_published, 404);

        $we = $workout->workoutExercises()->whereKey($workoutExerciseId)->first();
        abort_if($we === null, 422);
        abort_unless($setNumber >= 1 && $setNumber <= $we->sets, 422);

        $log = $this->ensureLog($user, $workout);

        return SetLog::updateOrCreate(
            [
                'workout_log_id' => $log->id,
                'exercise_id' => $we->exercise_id,
                'set_number' => $setNumber,
            ],
            ['completed' => $completed],
        );
    }

    /** The user's current in-progress log for this workout, or a fresh one. */
    public function ensureLog(User $user, Workout $workout): WorkoutLog
    {
        return WorkoutLog::where('user_id', $user->id)
            ->where('workout_id', $workout->id)
            ->whereNull('completed_at')
            ->latest('started_at')
            ->first()
            ?? WorkoutLog::create([
                'user_id' => $user->id,
                'workout_id' => $workout->id,
                'started_at' => now(),
            ]);
    }
}
