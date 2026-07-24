<?php

namespace App\Livewire;

use App\Enums\FocusArea;
use App\Models\Schedule;
use App\Models\Workout;
use App\Services\ScheduleGenerator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.dyno-live')]
class ScheduleBuilder extends Component
{
    /** @var array<string,int> focus_area => sessions/week */
    public array $frequencies = [];

    /** @var array<int,int> */
    public array $trainingDays = [0, 1, 2, 3, 4];

    /** @var array<int,int> declared climbing days (finger-maximal) */
    public array $climbingDays = [];

    /** @var array<int, array{focus:string, day:int}> the working (editable) week */
    public array $current = [];

    /** @var array<int, array<int, array{focus:string, day:int}>> candidate arrangements for shuffle */
    public array $candidates = [];

    public int $candidateIndex = 0;

    public bool $generated = false;

    public bool $saved = false;

    public function mount(): void
    {
        foreach (FocusArea::cases() as $c) {
            $this->frequencies[$c->value] = 0;
        }
        // Sensible starting point.
        $this->frequencies = array_merge($this->frequencies, [
            'grip' => 2, 'back' => 2, 'core' => 1, 'legs' => 1, 'push' => 1,
        ]);

        // Load the user's active schedule into the editor if one exists.
        $existing = Schedule::where('user_id', Auth::id())->where('active', true)->latest()->first();
        if ($existing) {
            $this->trainingDays = $existing->training_days ?? $this->trainingDays;
            $this->climbingDays = $existing->climbing_days ?? [];
            $this->frequencies = array_merge($this->frequencies, $existing->frequencies ?? []);
            $this->current = $existing->sessions->map(fn ($s) => [
                'focus' => $s->focus_area->value, 'day' => (int) $s->day_of_week,
            ])->all();
            $this->generated = ! empty($this->current);
        }
    }

    public function toggleTrainingDay(int $day): void
    {
        $this->trainingDays = $this->toggle($this->trainingDays, $day);
        // A day can't be both a training and a climbing day slot for our purposes.
        $this->climbingDays = array_values(array_diff($this->climbingDays, [$day]));
    }

    public function toggleClimbingDay(int $day): void
    {
        $this->climbingDays = $this->toggle($this->climbingDays, $day);
        $this->trainingDays = array_values(array_diff($this->trainingDays, [$day]));
    }

    public function generate(ScheduleGenerator $generator): void
    {
        $this->saved = false;
        $this->candidateIndex = 0;

        // These are plain public Livewire properties — sanitize before they
        // reach the generator (unbounded frequencies would let a tampered
        // request allocate an arbitrarily large sessions array).
        $this->trainingDays = $this->sanitizeDays($this->trainingDays);
        $this->climbingDays = $this->sanitizeDays($this->climbingDays);
        $this->frequencies = $this->sanitizeFrequencies($this->frequencies);

        $freq = array_filter($this->frequencies, fn ($n) => (int) $n > 0);
        $this->candidates = array_map(
            fn ($r) => $r['sessions'],
            $generator->generate($this->trainingDays, $this->climbingDays, $freq),
        );

        $this->current = $this->candidates[0] ?? [];
        $this->generated = true;
    }

    public function shuffle(): void
    {
        if (count($this->candidates) < 2) {
            return;
        }
        $this->candidateIndex = ($this->candidateIndex + 1) % count($this->candidates);
        $this->current = $this->candidates[$this->candidateIndex];
        $this->saved = false;
    }

    public function moveSession(int $index, int $direction): void
    {
        if (! isset($this->current[$index]) || empty($this->trainingDays)) {
            return;
        }
        $days = $this->trainingDays;
        sort($days);
        $pos = array_search($this->current[$index]['day'], $days, true);
        if ($pos === false) {
            $pos = 0;
        }
        $next = ($pos + $direction + count($days)) % count($days);
        $this->current[$index]['day'] = $days[$next];
        $this->saved = false;
    }

    public function removeSession(int $index): void
    {
        unset($this->current[$index]);
        $this->current = array_values($this->current);
        $this->saved = false;
    }

    public function save(): void
    {
        // `current` is client-mutable — validate every entry before persisting.
        // A junk focus string would poison the FocusArea enum cast on every
        // later read (dashboard, reminder cron); a junk day bricks the grid.
        if (! $this->currentIsValid()) {
            return;
        }

        $this->trainingDays = $this->sanitizeDays($this->trainingDays);
        $this->climbingDays = $this->sanitizeDays($this->climbingDays);
        $this->frequencies = $this->sanitizeFrequencies($this->frequencies);

        if (! empty($this->issuesProperty()['hard']) || empty($this->current)) {
            return;
        }

        DB::transaction(function () {
            Schedule::where('user_id', Auth::id())->update(['active' => false]);

            $schedule = Schedule::create([
                'user_id' => Auth::id(),
                'active' => true,
                'training_days' => array_values($this->trainingDays),
                'climbing_days' => array_values($this->climbingDays),
                'frequencies' => array_filter($this->frequencies, fn ($n) => (int) $n > 0),
            ]);

            // Rotate through published workouts of each focus area.
            $byFocus = Workout::where('is_published', true)->get()->groupBy(fn ($w) => $w->focus_area->value);
            $cursor = [];

            $positions = [];
            foreach ($this->current as $s) {
                $focus = $s['focus'];
                $pool = $byFocus->get($focus);
                $workoutId = null;
                if ($pool && $pool->count()) {
                    $i = $cursor[$focus] ?? 0;
                    $workoutId = $pool[$i % $pool->count()]->id;
                    $cursor[$focus] = $i + 1;
                }
                $day = (int) $s['day'];
                $positions[$day] = ($positions[$day] ?? -1) + 1;

                $schedule->sessions()->create([
                    'day_of_week' => $day,
                    'focus_area' => $focus,
                    'workout_id' => $workoutId,
                    'position' => $positions[$day],
                ]);
            }
        });

        $this->saved = true;
    }

    /** @return array{hard: array<int,string>, soft: array<int,string>} */
    public function issuesProperty(): array
    {
        return app(ScheduleGenerator::class)->issues($this->current, $this->climbingDays);
    }

    private function toggle(array $set, int $value): array
    {
        return in_array($value, $set, true)
            ? array_values(array_diff($set, [$value]))
            : array_values(array_merge($set, [$value]));
    }

    /** Keep only valid weekday ints (0=Mon…6=Sun). */
    private function sanitizeDays(array $days): array
    {
        return array_values(array_unique(array_filter(
            array_map(fn ($d) => is_numeric($d) ? (int) $d : -1, $days),
            fn ($d) => $d >= 0 && $d <= 6,
        )));
    }

    /** Keep only known focus areas, with counts clamped to a sane 0–7/week. */
    private function sanitizeFrequencies(array $frequencies): array
    {
        $out = [];
        foreach (FocusArea::cases() as $c) {
            $n = $frequencies[$c->value] ?? 0;
            $out[$c->value] = is_numeric($n) ? max(0, min(7, (int) $n)) : 0;
        }

        return $out;
    }

    /** Every session entry must carry a real focus area and an in-range day. */
    private function currentIsValid(): bool
    {
        if (count($this->current) > 14) { // hard ceiling: 2 sessions × 7 days
            return false;
        }
        foreach ($this->current as $s) {
            if (! is_array($s) || ! is_string($s['focus'] ?? null) || FocusArea::tryFrom($s['focus']) === null) {
                return false;
            }
            $day = $s['day'] ?? null;
            if (! is_numeric($day) || (int) $day < 0 || (int) $day > 6) {
                return false;
            }
        }

        return true;
    }

    public function render()
    {
        // Pre-build the 7-day grid so the Blade view stays declarative.
        $week = [];
        foreach (\App\Enums\DayOfWeek::cases() as $d) {
            $week[$d->value] = [
                'short' => $d->short(),
                'isClimbing' => in_array($d->value, $this->climbingDays, true),
                'isTraining' => in_array($d->value, $this->trainingDays, true),
                'sessions' => [],
            ];
        }
        foreach ($this->current as $index => $s) {
            // `current` is client-mutable; skip malformed entries instead of
            // crashing the grid on an unknown day index.
            if (! is_array($s) || ! is_string($s['focus'] ?? null)
                || ! is_numeric($s['day'] ?? null) || ! isset($week[(int) $s['day']])) {
                continue;
            }
            $focus = FocusArea::tryFrom($s['focus']);
            $week[(int) $s['day']]['sessions'][] = [
                'index' => $index,
                'label' => $focus?->label() ?? ucfirst($s['focus']),
                'accent' => $focus?->accentHex() ?? '#8A8A90',
            ];
        }

        return view('livewire.schedule-builder', [
            'issues' => $this->issuesProperty(),
            'week' => $week,
            'focusOptions' => FocusArea::options(),
            'dayLabels' => \App\Enums\DayOfWeek::shortMap(),
        ]);
    }
}
