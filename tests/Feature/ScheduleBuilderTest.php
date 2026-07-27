<?php

namespace Tests\Feature;

use App\Livewire\ScheduleBuilder;
use App\Models\Schedule;
use App\Models\User;
use App\Models\Workout;
use Database\Seeders\LibrarySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ScheduleBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_schedule(): void
    {
        $this->get(route('schedule'))->assertRedirect(route('login'));
    }

    public function test_member_can_open_the_builder(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('schedule'))->assertOk()->assertSee('Schedule');
    }

    public function test_adding_workouts_and_saving_persists_ordered_sessions(): void
    {
        $this->seed(LibrarySeeder::class);
        $user = User::factory()->create();
        $w1 = Workout::where('is_published', true)->first();
        $w2 = Workout::where('is_published', true)->where('id', '!=', $w1->id)->first();

        Livewire::actingAs($user)->test(ScheduleBuilder::class)
            ->call('addWorkout', 0, $w1->id)   // Monday
            ->call('addWorkout', 0, $w2->id)   // Monday, stacked
            ->call('addWorkout', 2, $w1->id)   // Wednesday
            ->call('save')
            ->assertSet('saved', true);

        $active = Schedule::where('user_id', $user->id)->where('active', true)->firstOrFail();
        $this->assertSame(3, $active->sessions()->count());

        $monday = $active->sessions()->where('day_of_week', 0)->orderBy('position')->get();
        $this->assertEquals([$w1->id, $w2->id], $monday->pluck('workout_id')->all());
        $this->assertEquals([0, 1], $monday->pluck('position')->all());
        // focus_area is denormalised from the chosen workout.
        $this->assertSame($w1->focus_area->value, $monday->first()->focus_area->value);
    }

    public function test_saving_deactivates_prior_active_schedule(): void
    {
        $this->seed(LibrarySeeder::class);
        $user = User::factory()->create();
        $old = Schedule::create(['user_id' => $user->id, 'active' => true]);
        $workout = Workout::where('is_published', true)->first();

        Livewire::actingAs($user)->test(ScheduleBuilder::class)
            ->call('addWorkout', 3, $workout->id)
            ->call('save')
            ->assertSet('saved', true);

        $this->assertFalse($old->fresh()->active);
        $this->assertSame(1, Schedule::where('user_id', $user->id)->where('active', true)->count());
    }

    public function test_workout_can_be_removed_before_saving(): void
    {
        $this->seed(LibrarySeeder::class);
        $user = User::factory()->create();
        $w1 = Workout::where('is_published', true)->first();
        $w2 = Workout::where('is_published', true)->where('id', '!=', $w1->id)->first();

        Livewire::actingAs($user)->test(ScheduleBuilder::class)
            ->call('addWorkout', 1, $w1->id)
            ->call('addWorkout', 1, $w2->id)
            ->call('removeWorkout', 1, 0)
            ->call('save')
            ->assertSet('saved', true);

        $active = Schedule::where('user_id', $user->id)->where('active', true)->firstOrFail();
        $this->assertSame(1, $active->sessions()->count());
        $this->assertEquals($w2->id, $active->sessions()->first()->workout_id);
    }

    public function test_unknown_or_unpublished_workout_is_rejected(): void
    {
        $user = User::factory()->create();
        $draft = Workout::create([
            'name' => 'Draft', 'focus_area' => 'grip', 'level' => 'beginner',
            'estimated_minutes' => 10, 'is_published' => false,
        ]);

        Livewire::actingAs($user)->test(ScheduleBuilder::class)
            ->call('addWorkout', 0, 999999)      // unknown id
            ->call('addWorkout', 0, $draft->id)  // unpublished
            ->assertSet('days.0', [])            // neither was added
            ->call('save')
            ->assertSet('saved', false);         // nothing to save

        $this->assertSame(0, Schedule::where('user_id', $user->id)->count());
    }

    public function test_today_surfaces_the_scheduled_session(): void
    {
        $this->seed(LibrarySeeder::class);
        $user = User::factory()->create();
        $todayNum = now()->dayOfWeekIso - 1;

        $schedule = Schedule::create(['user_id' => $user->id, 'active' => true]);
        $workout = Workout::where('is_published', true)->first();
        $schedule->sessions()->create([
            'day_of_week' => $todayNum,
            'focus_area' => $workout->focus_area->value,
            'workout_id' => $workout->id,
            'position' => 0,
        ]);

        $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertSee($workout->name);
    }
}
