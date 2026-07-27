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
        $this->saved = false;
    }

    public function removeWorkout(int $day, int $index): void
    {
        if (! isset($this->days[$day][$index])) {
            return;
        }
        unset($this->days[$day][$index]);
        $this->days[$day] = array_values($this->days[$day]);
        $this->saved = false;
    }

    public function save(): void
    {
        // `days` is client-supplied. Walk only the real weekday buckets, resolve
        // every id against the published-workout set (so an unpublished/foreign
        // id can't be persisted), and cap the total. Bail if nothing survives —
        // a tampered request must not create an empty active schedule or
        // deactivate the existing one.
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

        if (empty($rows)) {
            return;
        }

        DB::transaction(function () use ($rows) {
            Schedule::where('user_id', Auth::id())->update(['active' => false]);
            $schedule = Schedule::create(['user_id' => Auth::id(), 'active' => true]);
            $schedule->sessions()->createMany($rows);
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
