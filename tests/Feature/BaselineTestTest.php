<?php

namespace Tests\Feature;

use App\Livewire\BaselineTest;
use App\Livewire\WorkoutRunner;
use App\Models\TestResult;
use App\Models\User;
use App\Models\Workout;
use App\Models\WorkoutLog;
use Database\Seeders\LibrarySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BaselineTestTest extends TestCase
{
    use RefreshDatabase;

    private function logCompletedSessions(User $user, int $n): void
    {
        for ($i = 0; $i < $n; $i++) {
            WorkoutLog::create([
                'user_id' => $user->id,
                'started_at' => now()->subDays($i + 1),
                'completed_at' => now()->subDays($i + 1),
            ]);
        }
    }

    public function test_test_is_gated_until_enough_sessions_logged(): void
    {
        $user = User::factory()->create();
        $this->logCompletedSessions($user, 3); // grip needs 6

        $this->actingAs($user)->get(route('tests.run', 'grip_max_hang'))->assertForbidden();
    }

    public function test_test_unlocks_once_the_session_gate_is_met(): void
    {
        $user = User::factory()->create();
        $this->logCompletedSessions($user, 6);

        $this->actingAs($user)->get(route('tests.run', 'grip_max_hang'))->assertOk();
    }

    public function test_unknown_test_404s(): void
    {
        $user = User::factory()->create();
        $this->logCompletedSessions($user, 10);

        $this->actingAs($user)->get(route('tests.run', 'nope'))->assertNotFound();
    }

    public function test_result_cannot_be_recorded_before_warmup_is_complete(): void
    {
        $user = User::factory()->create();
        $this->logCompletedSessions($user, 6);

        Livewire::actingAs($user)
            ->test(BaselineTest::class, ['key' => 'grip_max_hang'])
            ->set('value', 50)
            ->call('record')
            ->assertStatus(422);

        $this->assertSame(0, TestResult::count());
    }

    public function test_result_is_recorded_after_warmup(): void
    {
        $user = User::factory()->create();
        $this->logCompletedSessions($user, 6);

        $component = Livewire::actingAs($user)->test(BaselineTest::class, ['key' => 'grip_max_hang']);
        // Check every warmup step.
        foreach (array_keys($component->get('warmupDone')) as $i) {
            $component->set("warmupDone.$i", true);
        }
        $component->set('value', 42.5)->call('record')->assertSet('finished', true);

        $this->assertDatabaseHas('test_results', [
            'user_id' => $user->id,
            'metric' => 'grip_max_hang',
            'focus_area' => 'grip',
            'value' => 42.50,
        ]);
    }

    public function test_progress_page_shows_locked_and_unlocked_states(): void
    {
        $user = User::factory()->create();
        $this->logCompletedSessions($user, 5); // back(4) unlocked, grip(6) locked

        $this->actingAs($user)->get(route('progress'))
            ->assertOk()
            ->assertSee('more session'); // the locked hint for grip
    }

    public function test_runner_resolves_percent_of_test_to_a_concrete_load(): void
    {
        $this->seed(LibrarySeeder::class);
        $user = User::factory()->create();

        // Grip baseline: 50kg added. A 90%-of-max hang should read ~45kg.
        TestResult::create([
            'user_id' => $user->id,
            'focus_area' => 'grip',
            'metric' => 'grip_max_hang',
            'value' => 50,
            'unit' => 'kg',
            'tested_at' => now(),
        ]);

        $workout = Workout::where('name', 'Hangboard Strength — Max Hangs')->firstOrFail();

        Livewire::actingAs($user)
            ->test(WorkoutRunner::class, ['workout' => $workout])
            ->assertSee('45kg'); // 90% of 50kg
    }
}
