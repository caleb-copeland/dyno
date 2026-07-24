<?php

namespace App\Http\Controllers;

use App\Actions\LogSet;
use App\Models\Workout;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SetLogController extends Controller
{
    /** JSON endpoint the runner's offline queue flushes to (one set completion). */
    public function store(Request $request, LogSet $logSet): JsonResponse
    {
        $data = $request->validate([
            'workout_id' => ['required', 'integer'],
            'workout_exercise_id' => ['required', 'integer'],
            'set_number' => ['required', 'integer', 'min:1', 'max:65535'],
            'completed' => ['required', 'boolean'],
        ]);

        $workout = Workout::findOrFail($data['workout_id']);

        $logSet->handle(
            user: $request->user(),
            workout: $workout,
            workoutExerciseId: $data['workout_exercise_id'],
            setNumber: $data['set_number'],
            completed: $data['completed'],
        );

        return response()->json(['ok' => true]);
    }
}
