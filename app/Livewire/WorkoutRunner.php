<?php

namespace App\Livewire;

use App\Models\SetLog;
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
                $parts[] = (int) round($we->percent_of_test * 100).'% max';
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

    /** Toggle a single set's completed state and persist it. */
    public function toggleSet(int $weId, ?int $exerciseId, int $setNumber): void
    {
        // All action args are client-supplied. Resolve the row against THIS
        // workout and derive exercise_id server-side ($exerciseId is ignored)
        // so a crafted call can't persist set logs for arbitrary exercises,
        // mismatched exercise ids, or out-of-range set numbers.
        $we = $this->workout->workoutExercises()->whereKey($weId)->first();
        abort_if($we === null, 422);
        abort_unless($setNumber >= 1 && $setNumber <= $we->sets, 422);

        $log = $this->ensureLog();
        $key = "{$weId}:{$setNumber}";
        $newState = ! ($this->done[$key] ?? false);

        SetLog::updateOrCreate(
            [
                'workout_log_id' => $log->id,
                'exercise_id' => $we->exercise_id,
                'set_number' => $setNumber,
            ],
            ['completed' => $newState],
        );

        $this->done[$key] = $newState;
    }

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
