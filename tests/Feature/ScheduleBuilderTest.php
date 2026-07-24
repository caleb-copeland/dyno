<?php

namespace Tests\Feature;

use App\Livewire\ScheduleBuilder;
use App\Models\Schedule;
use App\Models\User;
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

    public function test_generate_produces_an_editable_week(): void
    {
        $user = User::factory()->create();

        // 6 sessions requested → 6 placed, and marked generated.
        $component = Livewire::actingAs($user)->test(ScheduleBuilder::class)
            ->set('frequencies', ['grip' => 2, 'back' => 2, 'core' => 1, 'legs' => 1])
            ->set('trainingDays', [0, 1, 2, 3, 4])
            ->set('climbingDays', [5])
            ->call('generate')
            ->assertSet('generated', true);

        $this->assertCount(6, $component->get('current'));
    }

    public function test_saving_persists_an_active_schedule_and_deactivates_prior(): void
    {
        $this->seed(LibrarySeeder::class);
        $user = User::factory()->create();

        // A pre-existing active schedule that must be deactivated.
        $old = Schedule::create(['user_id' => $user->id, 'active' => true, 'training_days' => [0]]);

        Livewire::actingAs($user)
            ->test(ScheduleBuilder::class)
            ->set('frequencies', ['grip' => 1, 'back' => 1, 'legs' => 1])
            ->set('trainingDays', [0, 2, 4])
            ->set('climbingDays', [])
            ->call('generate')
            ->call('save')
            ->assertSet('saved', true);

        $this->assertFalse($old->fresh()->active);
        $active = Schedule::where('user_id', $user->id)->where('active', true)->first();
        $this->assertNotNull($active);
        $this->assertGreaterThan(0, $active->sessions()->count());
        // Grip session should have been assigned a published grip workout.
        $grip = $active->sessions()->where('focus_area', 'grip')->first();
        $this->assertNotNull($grip->workout_id);
    }

    public function test_save_is_blocked_when_a_hard_rule_is_violated(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ScheduleBuilder::class)
            // Force a hard violation: grip on Mon, climbing Tue (consecutive finger days).
            ->set('current', [['focus' => 'grip', 'day' => 0]])
            ->set('climbingDays', [1])
            ->set('generated', true)
            ->call('save')
            ->assertSet('saved', false);

        $this->assertSame(0, Schedule::where('user_id', $user->id)->count());
    }

    public function test_today_surfaces_the_scheduled_session(): void
    {
        $this->seed(LibrarySeeder::class);
        $user = User::factory()->create();
        $todayNum = now()->dayOfWeekIso - 1;

        $schedule = Schedule::create(['user_id' => $user->id, 'active' => true, 'training_days' => [$todayNum]]);
        $workout = \App\Models\Workout::where('is_published', true)->first();
        $schedule->sessions()->create([
            'day_of_week' => $todayNum,
            'focus_area' => $workout->focus_area->value,
            'workout_id' => $workout->id,
            'position' => 0,
        ]);

        $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertSee($workout->name);
    }
}
