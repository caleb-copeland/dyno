<?php

namespace App\Http\Controllers;

use App\Models\Workout;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class TrainController extends Controller
{
    /** "Today" — until the scheduler (step 5) lands, a manual workout picker. */
    public function today(Request $request): View
    {
        $workouts = Workout::query()
            ->where('is_published', true)
            ->withCount('workoutExercises')
            ->orderBy('focus_area')
            ->orderBy('name')
            ->get()
            ->groupBy(fn (Workout $w) => $w->focus_area->label());

        $recent = $request->user()->workoutLogs()
            ->with('workout')
            ->whereNotNull('completed_at')
            ->latest('completed_at')
            ->limit(3)
            ->get();

        return view('train.today', compact('workouts', 'recent'));
    }

    public function history(Request $request): View
    {
        $logs = $request->user()->workoutLogs()
            ->with('workout')
            ->withCount(['setLogs as completed_sets_count' => fn ($q) => $q->where('completed', true)])
            ->latest('started_at')
            ->paginate(20);

        return view('train.history', compact('logs'));
    }
}
