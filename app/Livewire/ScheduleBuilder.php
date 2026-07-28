<?php

namespace App\Livewire;

use App\Enums\DayOfWeek;
use App\Models\Schedule;
use App\Models\Workout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.dyno-live')]
class ScheduleBuilder extends Component
{
    /** Ceilings — `days` is client-mutable, so bound what a request can allocate. */
    private const MAX_PER_DAY = 6;

    private const MAX_TOTAL = 42;

    /** @var array<int, array<int, int>> day (0=Mon…6=Sun) => ordered workout ids */
    public array $days = [];

    public bool $saved = false;

    public function mount(): void
    {
        foreach (range(0, 6) as $d) {
            $this->days[$d] = [];
        }

        $existing = Schedule::where('user_id', Auth::id())
            ->where('active', true)
            ->with('sessions')
            ->latest()
            ->first();

        if ($existing) {
            foreach ($existing->sessions as $s) {
                if ($s->workout_id !== null && isset($this->days[(int) $s->day_of_week])) {
                    $this->days[(int) $s->day_of_week][] = (int) $s->workout_id;
                }
            }
        }
    }

    public function addWorkout(int $day, mixed $workoutId): void
    {
        if ($day < 0 || $day > 6 || ! is_numeric($workoutId)) {
            return;
        }
        if (count($this->days[$day] ?? []) >= self::MAX_PER_DAY) {
            return;
        }
        $id = (int) $workoutId;
        if (! Workout::where('id', $id)->where('is_published', true)->exists()) {
            return;
        }

        $this->days[$day][] = $id;
        $this->persist();
    }

    public function removeWorkout(int $day, int $index): void
    {
        if (! isset($this->days[$day][$index])) {
            return;
        }
        unset($this->days[$day][$index]);
        $this->days[$day] = array_values($this->days[$day]);
        $this->persist();
    }

    /** Alias kept for explicit "save" triggers; the editor auto-persists. */
    public function save(): void
    {
        $this->persist();
    }

    /**
     * Write the current week to the user's single active schedule. `days` is
     * client-supplied, so walk only real weekday buckets, resolve every id
     * against the published-workout set (an unpublished/foreign id can't be
     * persisted), and cap the total. The active schedule is reused in place so
     * rapid auto-saves don't pile up rows; a tampered request that resolves to
     * nothing when no schedule exists yet is a no-op.
     */
    private function persist(): void
    {
        $workouts = Workout::where('is_published', true)->get()->keyBy('id');

        $rows = [];
        foreach (range(0, 6) as $day) {
            $position = 0;
            foreach ((array) ($this->days[$day] ?? []) as $wid) {
                if (! is_numeric($wid)) {
                    continue;
                }
                $workout = $workouts->get((int) $wid);
                if ($workout === null) {
                    continue;
                }
                if (count($rows) >= self::MAX_TOTAL) {
                    break 2;
                }
                $rows[] = [
                    'day_of_week' => $day,
                    'focus_area' => $workout->focus_area->value,
                    'workout_id' => $workout->id,
                    'position' => $position++,
                ];
            }
        }

        $existing = Schedule::where('user_id', Auth::id())->where('active', true)->latest()->first();

        // Nothing to store and no schedule to update — don't materialise an
        // empty one (covers tampered input as well as a first-time no-op).
        if ($existing === null && $rows === []) {
            return;
        }

        DB::transaction(function () use ($rows, $existing) {
            $schedule = $existing ?? Schedule::create(['user_id' => Auth::id(), 'active' => true]);

            // Collapse any stray extra active schedules onto this one.
            Schedule::where('user_id', Auth::id())
                ->where('active', true)
                ->where('id', '!=', $schedule->id)
                ->update(['active' => false]);

            $schedule->sessions()->delete();
            if ($rows !== []) {
                $schedule->sessions()->createMany($rows);
            }
        });

        $this->saved = true;
    }

    public function render()
    {
        $published = Workout::where('is_published', true)->orderBy('name')->get();
        $byId = $published->keyBy('id');

        $week = [];
        foreach (DayOfWeek::cases() as $d) {
            $items = [];
            foreach ($this->days[$d->value] ?? [] as $index => $wid) {
                $workout = $byId->get((int) $wid);
                if ($workout === null) {
                    continue;
                }
                $items[] = [
                    'index' => $index,
                    'name' => $workout->name,
                    'focus' => $workout->focus_area->label(),
                    'accent' => $workout->focus_area->accentHex(),
                    'minutes' => $workout->estimated_minutes,
                ];
            }
            $week[$d->value] = ['label' => $d->label(), 'items' => $items];
        }

        return view('livewire.schedule-builder', [
            'week' => $week,
            'options' => $published->groupBy(fn (Workout $w) => $w->focus_area->label()),
        ]);
    }
}
