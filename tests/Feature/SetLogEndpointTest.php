<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workout;
use App\Models\WorkoutLog;
use Database\Seeders\LibrarySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** The JSON endpoint the runner's offline queue flushes to. */
class SetLogEndpointTest extends TestCase
{
    use RefreshDatabase;

    private function workout(): Workout
    {
        $this->seed(LibrarySeeder::class);

        return Workout::where('is_published', true)->firstOrFail();
    }

    public function test_guest_cannot_log_a_set(): void
    {
        $workout = $this->workout();
        $item = $workout->workoutExercises()->first();

        $this->postJson(route('api.set-log'), [
            'workout_id' => $workout->id,
            'workout_exercise_id' => $item->id,
            'set_number' => 1,
            'completed' => true,
        ])->assertUnauthorized();
    }

    public function test_a_member_can_log_and_unlog_a_set(): void
    {
        $workout = $this->workout();
        $item = $workout->workoutExercises()->first();
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('api.set-log'), [
            'workout_id' => $workout->id,
            'workout_exercise_id' => $item->id,
            'set_number' => 1,
            'completed' => true,
        ])->assertOk()->assertJson(['ok' => true]);

        $log = WorkoutLog::where('user_id', $user->id)->firstOrFail();
        $this->assertDatabaseHas('set_logs', [
            'workout_log_id' => $log->id,
            'exercise_id' => $item->exercise_id,
            'set_number' => 1,
            'completed' => true,
        ]);

        // Flip it back off — same row, no duplicate.
        $this->actingAs($user)->postJson(route('api.set-log'), [
            'workout_id' => $workout->id,
            'workout_exercise_id' => $item->id,
            'set_number' => 1,
            'completed' => false,
        ])->assertOk();

        $this->assertSame(1, \App\Models\SetLog::count());
        $this->assertDatabaseHas('set_logs', ['set_number' => 1, 'completed' => false]);
    }

    public function test_a_row_from_another_workout_is_rejected(): void
    {
        $workout = $this->workout();
        $other = Workout::where('id', '!=', $workout->id)->firstOrFail();
        $foreign = $other->workoutExercises()->firstOrFail();
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('api.set-log'), [
            'workout_id' => $workout->id,
            'workout_exercise_id' => $foreign->id,
            'set_number' => 1,
            'completed' => true,
        ])->assertStatus(422);

        $this->assertSame(0, \App\Models\SetLog::count());
    }

    public function test_out_of_range_set_number_is_rejected(): void
    {
        $workout = $this->workout();
        $item = $workout->workoutExercises()->first();
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('api.set-log'), [
            'workout_id' => $workout->id,
            'workout_exercise_id' => $item->id,
            'set_number' => $item->sets + 5,
            'completed' => true,
        ])->assertStatus(422);
    }

    public function test_unpublished_workout_is_rejected(): void
    {
        $workout = $this->workout();
        $item = $workout->workoutExercises()->first();
        $workout->update(['is_published' => false]);
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('api.set-log'), [
            'workout_id' => $workout->id,
            'workout_exercise_id' => $item->id,
            'set_number' => 1,
            'completed' => true,
        ])->assertNotFound();
    }
}
