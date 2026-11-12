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

    /**
     * Which warmup steps are checked. Locked so the client can't reshape it
     * (e.g. send [true] for a 3-step warmup) — steps flip via toggleWarmup().
     *
     * @var array<int,bool>
     */
    #[Locked]
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

    public function toggleWarmup(int $step): void
    {
        if (array_key_exists($step, $this->warmupDone)) {
            $this->warmupDone[$step] = ! $this->warmupDone[$step];
        }
    }

    public function getWarmupCompleteProperty(): bool
    {
        return count($this->warmupDone) > 0 && ! in_array(false, $this->warmupDone, true);
    }

    public function record(): void
    {
        abort_unless($this->warmupComplete, 422); // every warmup step checked first

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
