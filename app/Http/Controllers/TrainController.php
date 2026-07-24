<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Models\Workout;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class TrainController extends Controller
{
    /** "Today" — today's scheduled session(s) as the hero, or a manual picker. */
    public function today(Request $request): View
    {
        // 0 = Monday … 6 = Sunday (Carbon ISO is 1..7).
        $todayNum = now()->dayOfWeekIso - 1;

        $schedule = Schedule::where('user_id', $request->user()->id)
            ->where('active', true)
            ->with(['sessions' => fn ($q) => $q->where('day_of_week', $todayNum), 'sessions.workout'])
            ->latest()
            ->first();

        $todaySessions = $schedule ? $schedule->sessions : collect();
        $isClimbingToday = $schedule && in_array($todayNum, $schedule->climbing_days ?? [], true);

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

        return view('train.today', compact(
            'workouts', 'recent', 'todaySessions', 'isClimbingToday', 'schedule',
        ));
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
