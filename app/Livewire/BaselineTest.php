<?php

namespace App\Livewire;

use App\Enums\FocusArea;
use App\Models\TestResult;
use App\Support\BaselineTests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('layouts.dyno-live')]
class BaselineTest extends Component
{
    #[Locked]
    public string $key;

    /** @var array<string,mixed> */
    #[Locked]
    public array $def;

    /** @var array<int,bool> which warmup steps are checked */
    public array $warmupDone = [];

    public ?float $value = null;

    public bool $finished = false;

    public function mount(string $key): void
    {
        $def = BaselineTests::find($key);
        abort_if($def === null, 404);

        // Gate: max tests are an injury vector — require a training base first.
        abort_if(Auth::user()->completedSessionsCount() < $def['min_sessions'], 403);

        $this->key = $key;
        $this->def = $def;
        $this->warmupDone = array_fill(0, count($def['warmup']), false);
    }

    public function getWarmupCompleteProperty(): bool
    {
        return count($this->warmupDone) > 0 && ! in_array(false, $this->warmupDone, true);
    }

    public function record(): void
    {
        abort_unless($this->warmupComplete, 422); // no unwarmed max test

        $this->validate([
            'value' => ['required', 'numeric', 'min:0', 'max:1000'],
        ]);

        TestResult::create([
            'user_id' => Auth::id(),
            'focus_area' => FocusArea::from($this->def['focus']),
            'metric' => $this->key,
            'value' => $this->value,
            'unit' => $this->def['unit'],
            'tested_at' => now(),
        ]);

        $this->finished = true;
    }

    public function render()
    {
        return view('livewire.baseline-test');
    }
}
