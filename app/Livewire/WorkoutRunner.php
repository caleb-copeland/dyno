<?php

namespace App\Livewire;

use App\Actions\LogSet;
use App\Models\Workout;
use App\Models\WorkoutLog;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('layouts.runner')]
class WorkoutRunner extends Component
{
    public Workout $workout;

    public ?int $logId = null;

    /**
     * Flattened, ordered exercise items. Locked: server-derived only — values
     * (rest_s, interval_*) are interpolated into Alpine expressions in the
     * view, so a client-tampered item must never round-trip back into markup.
     *
     * @var array<int, array<string, mixed>>
     */
    #[Locked]
    public array $items = [];

    /** completion map keyed "{workoutExerciseId}:{setNumber}" => bool */
    public array $done = [];

    public bool $finished = false;

    public string $sessionNotes = '';

    public ?int $perceivedEffort = null;

    public function mount(Workout $workout): void
    {
        abort_unless($workout->is_published, 404);

        $this->workout = $workout->load('workoutExercises.exercise');
        $this->buildItems();

        // Resume an in-progress session for this user+workout, if any.
        $existing = WorkoutLog::where('user_id', Auth::id())
            ->where('workout_id', $workout->id)
            ->whereNull('completed_at')
            ->latest('started_at')
            ->first();

        if ($existing) {
            $this->logId = $existing->id;
            $this->hydrateDoneFrom($existing);
        }
    }

    protected function buildItems(): void
    {
        $this->items = $this->workout->workoutExercises->map(function ($we) {
            $exercise = $we->exercise;

            // Prescription summary built here (not in Blade) to keep the runner
            // view free of echo-less inline conditionals.
            $parts = ["{$we->sets} sets"];
            if ($we->target_reps) {
                $parts[] = "{$we->target_reps} reps";
            }
            if ($we->target_duration_s) {
                $parts[] = "{$we->target_duration_s}s hold";
            }
            if ($we->prescription_basis->value === 'percent_of_test' && $we->percent_of_test) {
                $pct = (int) round($we->percent_of_test * 100);
                // Resolve to a concrete load from the user's latest baseline test.
                $target = $exercise ? $this->percentTarget($exercise->focus_area, (float) $we->percent_of_test) : null;
                $parts[] = $target ? "{$target} ({$pct}% max)" : "{$pct}% max";
            }
            if ($we->prescription_basis->value === 'rpe') {
                $parts[] = 'RPE';
            }
            if ($we->rest_s) {
                $parts[] = "{$we->rest_s}s rest";
            }

            return [
                'we_id' => $we->id,
                'exercise_id' => $we->exercise_id,
                'name' => $exercise?->name ?? 'Exercise',
                'focus' => $exercise?->focus_area->value,
                'accent' => $exercise?->focus_area->accentHex() ?? '#8A8A90',
                'type' => $exercise?->prescription_type->value,
                'finger' => (bool) $exercise?->is_finger_intensive,
                'instructions' => $exercise?->instructions,
                'sets' => (int) $we->sets,
                'target_reps' => $we->target_reps,
                'target_duration_s' => $we->target_duration_s,
                'rest_s' => $we->rest_s,
                'interval_work_s' => $we->interval_work_s,
                'interval_rest_s' => $we->interval_rest_s,
                'interval_reps' => $we->interval_reps,
                'basis' => $we->prescription_basis->value,
                'percent' => $we->percent_of_test,
                'summary' => implode(' · ', $parts),
            ];
        })->all();
    }

    /** Latest baseline result for a focus area × percentage → "40kg", or null if untested. */
    private function percentTarget(\App\Enums\FocusArea $focus, float $percent): ?string
    {
        $key = \App\Support\BaselineTests::forFocus($focus);
        if (! $key) {
            return null;
        }

        $latest = \App\Models\TestResult::where('user_id', Auth::id())
            ->where('metric', $key)
            ->latest('tested_at')
            ->first();

        if (! $latest) {
            return null;
        }

        $target = round((float) $latest->value * $percent, 1);

        return rtrim(rtrim(number_format($target, 1), '0'), '.').$latest->unit;
    }

    protected function hydrateDoneFrom(WorkoutLog $log): void
    {
        // Map persisted set completions back onto the per-row UI keys.
        $byExercise = $log->setLogs()->where('completed', true)->get()
            ->groupBy('exercise_id');

        foreach ($this->items as $item) {
            $sets = $byExercise->get($item['exercise_id']);
            if (! $sets) {
                continue;
            }
            foreach ($sets as $setLog) {
                $this->done["{$item['we_id']}:{$setLog->set_number}"] = true;
            }
        }
    }

    protected function ensureLog(): WorkoutLog
    {
        // logId is a client-modifiable Livewire property — always scope the
        // lookup to the authenticated user + this workout so a tampered id
        // can't write into someone else's session (IDOR).
        if ($this->logId) {
            $log = WorkoutLog::where('id', $this->logId)
                ->where('user_id', Auth::id())
                ->where('workout_id', $this->workout->id)
                ->first();

            if ($log) {
                return $log;
            }

            $this->logId = null; // stale/forged — fall through and start fresh
        }

        $log = WorkoutLog::create([
            'user_id' => Auth::id(),
            'workout_id' => $this->workout->id,
            'started_at' => now(),
        ]);

        $this->logId = $log->id;

        return $log;
    }

    /**
     * Toggle a single set (online path). Delegates to the LogSet action — the
     * same validation/scoping the offline queue's endpoint uses. $exerciseId is
     * accepted for signature stability but ignored; the action derives it.
     */
    public function toggleSet(int $weId, ?int $exerciseId, int $setNumber, LogSet $logSet): void
    {
        $key = "{$weId}:{$setNumber}";
        $newState = ! ($this->done[$key] ?? false);

        $setLog = $logSet->handle(Auth::user(), $this->workout, $weId, $setNumber, $newState);

        $this->logId = $setLog->workout_log_id;
        $this->done[$key] = $newState;
    }

    /** Completed-set count read from persistence (the offline queue writes there too). */
    public int $completedCount = 0;

    public function completeSession(): void
    {
        $this->validate([
            'perceivedEffort' => ['nullable', 'integer', 'min:1', 'max:10'],
            'sessionNotes' => ['nullable', 'string', 'max:2000'],
        ]);

        $log = $this->ensureLog();
        $log->update([
            'completed_at' => now(),
            'notes' => $this->sessionNotes ?: null,
            'perceived_effort' => $this->perceivedEffort,
        ]);

        $this->completedCount = $log->setLogs()->where('completed', true)->count();
        $this->finished = true;
        $this->dispatch('session-finished'); // release wake lock client-side
    }

    public function getTotalSetsProperty(): int
    {
        return collect($this->items)->sum('sets');
    }

    public function getCompletedSetsProperty(): int
    {
        return collect($this->done)->filter()->count();
    }

    public function render()
    {
        return view('livewire.workout-runner');
    }
}
