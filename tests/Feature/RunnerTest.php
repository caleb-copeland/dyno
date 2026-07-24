<?php

namespace Tests\Feature;

use App\Livewire\WorkoutRunner;
use App\Models\SetLog;
use App\Models\User;
use App\Models\Workout;
use App\Models\WorkoutLog;
use Database\Seeders\LibrarySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RunnerTest extends TestCase
{
    use RefreshDatabase;

    private function seededWorkout(): Workout
    {
        $this->seed(LibrarySeeder::class);

        return Workout::where('is_published', true)->firstOrFail();
    }

    public function test_guest_is_redirected_from_runner(): void
    {
        $workout = $this->seededWorkout();

        $this->get(route('run', $workout))->assertRedirect(route('login'));
    }

    public function test_member_can_open_the_runner(): void
    {
        $workout = $this->seededWorkout();
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('run', $workout))->assertOk()->assertSee($workout->name);
    }

    public function test_unpublished_workout_is_not_runnable(): void
    {
        $this->seed(LibrarySeeder::class);
        $workout = Workout::first();
        $workout->update(['is_published' => false]);
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('run', $workout))->assertNotFound();
    }

    public function test_toggling_a_set_starts_a_log_and_records_the_set(): void
    {
        $workout = $this->seededWorkout();
        $user = User::factory()->create();
        $item = $workout->workoutExercises()->first();

        Livewire::actingAs($user)
            ->test(WorkoutRunner::class, ['workout' => $workout])
            ->call('toggleSet', $item->id, $item->exercise_id, 1)
            ->assertSet('done', ["{$item->id}:1" => true]);

        $log = WorkoutLog::where('user_id', $user->id)->where('workout_id', $workout->id)->first();
        $this->assertNotNull($log);
        $this->assertDatabaseHas('set_logs', [
            'workout_log_id' => $log->id,
            'exercise_id' => $item->exercise_id,
            'set_number' => 1,
            'completed' => true,
        ]);
    }

    public function test_toggling_twice_marks_the_set_incomplete(): void
    {
        $workout = $this->seededWorkout();
        $user = User::factory()->create();
        $item = $workout->workoutExercises()->first();

        Livewire::actingAs($user)
            ->test(WorkoutRunner::class, ['workout' => $workout])
            ->call('toggleSet', $item->id, $item->exercise_id, 1)
            ->call('toggleSet', $item->id, $item->exercise_id, 1)
            ->assertSet('done', ["{$item->id}:1" => false]);

        $this->assertDatabaseHas('set_logs', [
            'set_number' => 1,
            'completed' => false,
        ]);
    }

    public function test_completing_a_session_stamps_the_log(): void
    {
        $workout = $this->seededWorkout();
        $user = User::factory()->create();
        $item = $workout->workoutExercises()->first();

        Livewire::actingAs($user)
            ->test(WorkoutRunner::class, ['workout' => $workout])
            ->call('toggleSet', $item->id, $item->exercise_id, 1)
            ->set('perceivedEffort', 8)
            ->set('sessionNotes', 'Felt strong.')
            ->call('completeSession')
            ->assertSet('finished', true);

        $log = WorkoutLog::where('user_id', $user->id)->firstOrFail();
        $this->assertNotNull($log->completed_at);
        $this->assertSame(8, $log->perceived_effort);
        $this->assertSame('Felt strong.', $log->notes);
    }

    public function test_an_in_progress_session_is_resumed(): void
    {
        $workout = $this->seededWorkout();
        $user = User::factory()->create();
        $item = $workout->workoutExercises()->first();

        // First mount: log one set.
        Livewire::actingAs($user)
            ->test(WorkoutRunner::class, ['workout' => $workout])
            ->call('toggleSet', $item->id, $item->exercise_id, 1);

        // Fresh mount should rehydrate the completed set.
        Livewire::actingAs($user)
            ->test(WorkoutRunner::class, ['workout' => $workout])
            ->assertSet('done', ["{$item->id}:1" => true]);

        $this->assertSame(1, WorkoutLog::where('user_id', $user->id)->count());
    }

    public function test_effort_is_validated(): void
    {
        $workout = $this->seededWorkout();
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(WorkoutRunner::class, ['workout' => $workout])
            ->set('perceivedEffort', 99)
            ->call('completeSession')
            ->assertHasErrors('perceivedEffort');
    }

    public function test_a_tampered_log_id_cannot_write_into_another_users_session(): void
    {
        $workout = $this->seededWorkout();
        $victim = User::factory()->create();
        $attacker = User::factory()->create();
        $item = $workout->workoutExercises()->first();

        // Victim starts a real session.
        $victimLog = WorkoutLog::create([
            'user_id' => $victim->id,
            'workout_id' => $workout->id,
            'started_at' => now(),
        ]);

        // Attacker forges the public logId property to the victim's log.
        Livewire::actingAs($attacker)
            ->test(WorkoutRunner::class, ['workout' => $workout])
            ->set('logId', $victimLog->id)
            ->call('toggleSet', $item->id, $item->exercise_id, 1);

        // The victim's log must be untouched; the write lands on the attacker's own new log.
        $this->assertDatabaseMissing('set_logs', ['workout_log_id' => $victimLog->id]);
        $this->assertSame(1, WorkoutLog::where('user_id', $attacker->id)->count());
    }

    public function test_toggle_set_rejects_an_exercise_row_from_another_workout(): void
    {
        $workout = $this->seededWorkout();
        $other = Workout::where('id', '!=', $workout->id)->firstOrFail();
        $foreignItem = $other->workoutExercises()->firstOrFail();
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(WorkoutRunner::class, ['workout' => $workout])
            ->call('toggleSet', $foreignItem->id, $foreignItem->exercise_id, 1)
            ->assertStatus(422);

        $this->assertSame(0, SetLog::count());
    }

    public function test_toggle_set_ignores_a_forged_exercise_id(): void
    {
        $workout = $this->seededWorkout();
        $user = User::factory()->create();
        $item = $workout->workoutExercises()->first();

        // Forge an exercise_id that doesn't belong to this workout row.
        $foreignExercise = \App\Models\Exercise::where('id', '!=', $item->exercise_id)->firstOrFail();

        Livewire::actingAs($user)
            ->test(WorkoutRunner::class, ['workout' => $workout])
            ->call('toggleSet', $item->id, $foreignExercise->id, 1);

        // The persisted row must use the server-side exercise for that row.
        $this->assertDatabaseHas('set_logs', ['exercise_id' => $item->exercise_id, 'set_number' => 1]);
        $this->assertDatabaseMissing('set_logs', ['exercise_id' => $foreignExercise->id]);
    }

    public function test_toggle_set_rejects_out_of_range_set_numbers(): void
    {
        $workout = $this->seededWorkout();
        $user = User::factory()->create();
        $item = $workout->workoutExercises()->first();

        foreach ([0, -1, $item->sets + 1, 60000] as $bad) {
            Livewire::actingAs($user)
                ->test(WorkoutRunner::class, ['workout' => $workout])
                ->call('toggleSet', $item->id, $item->exercise_id, $bad)
                ->assertStatus(422);
        }

        $this->assertSame(0, SetLog::count());
    }

    public function test_items_property_cannot_be_tampered_from_the_client(): void
    {
        $workout = $this->seededWorkout();
        $user = User::factory()->create();

        $this->expectException(\Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException::class);

        Livewire::actingAs($user)
            ->test(WorkoutRunner::class, ['workout' => $workout])
            ->set('items', [['we_id' => 1, 'rest_s' => "1);alert('xss');//", 'sets' => 1]]);
    }

    public function test_today_and_history_pages_render(): void
    {
        $workout = $this->seededWorkout();
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertSee($workout->name);
        $this->actingAs($user)->get(route('history'))->assertOk();
    }
}
