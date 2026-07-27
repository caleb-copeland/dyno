<?php

namespace Tests\Feature;

use App\Livewire\ScheduleBuilder;
use App\Models\Schedule;
use App\Models\User;
use App\Models\Workout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Regression tests for the step 5–8 security audit: Livewire property
 * tampering on the schedule builder, and SSRF via the push subscribe endpoint.
 */
class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    // ---- ScheduleBuilder: client-mutable properties must be sanitized ----

    public function test_save_rejects_foreign_or_unpublished_workouts(): void
    {
        $user = User::factory()->create();
        // Unpublished workouts and non-existent ids must not reach the schedule;
        // a foreign focus would otherwise poison the FocusArea cast downstream.
        $draft = Workout::create([
            'name' => 'Draft', 'focus_area' => 'grip', 'level' => 'beginner',
            'estimated_minutes' => 10, 'is_published' => false,
        ]);

        Livewire::actingAs($user)
            ->test(ScheduleBuilder::class)
            ->set('days.0', [$draft->id, 999999])
            ->call('save')
            ->assertSet('saved', false);

        // Nothing valid survived, so no empty active schedule is created either.
        $this->assertSame(0, Schedule::where('user_id', $user->id)->count());
    }

    public function test_save_ignores_out_of_range_days(): void
    {
        $this->seed(\Database\Seeders\LibrarySeeder::class);
        $user = User::factory()->create();
        $workout = Workout::where('is_published', true)->first();

        Livewire::actingAs($user)
            ->test(ScheduleBuilder::class)
            ->set('days.42', [$workout->id]) // bogus weekday bucket
            ->call('save')
            ->assertSet('saved', false);

        $this->assertSame(0, Schedule::where('user_id', $user->id)->count());
    }

    public function test_save_caps_total_sessions(): void
    {
        $this->seed(\Database\Seeders\LibrarySeeder::class);
        $user = User::factory()->create();
        $workout = Workout::where('is_published', true)->first();

        // The client can send any-sized array; the server caps what it persists.
        Livewire::actingAs($user)
            ->test(ScheduleBuilder::class)
            ->set('days.0', array_fill(0, 100, $workout->id))
            ->call('save')
            ->assertSet('saved', true);

        $active = Schedule::where('user_id', $user->id)->where('active', true)->firstOrFail();
        $this->assertLessThanOrEqual(42, $active->sessions()->count());
    }

    // ---- Push subscribe: the server later POSTs to this URL (SSRF) ----

    public function test_push_subscribe_rejects_non_https_endpoints(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('api.push.subscribe'), [
            'endpoint' => 'http://attacker.example/collect',
            'keys' => ['p256dh' => 'k', 'auth' => 'a'],
        ])->assertStatus(422)->assertJsonValidationErrors('endpoint');
    }

    public function test_push_subscribe_rejects_ip_literal_and_localhost_endpoints(): void
    {
        $user = User::factory()->create();

        foreach ([
            'https://127.0.0.1/internal',
            'https://169.254.169.254/latest/meta-data',
            'https://[::1]/internal',
            'https://localhost/internal',
            'https://foo.localhost/internal',
            'not-a-url',
        ] as $endpoint) {
            $this->actingAs($user)->postJson(route('api.push.subscribe'), [
                'endpoint' => $endpoint,
                'keys' => ['p256dh' => 'k', 'auth' => 'a'],
            ])->assertStatus(422)->assertJsonValidationErrors('endpoint');
        }

        $this->assertSame(0, \App\Models\PushSubscription::count());
    }

    public function test_push_subscribe_still_accepts_a_real_push_service_endpoint(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('api.push.subscribe'), [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc123',
            'keys' => ['p256dh' => 'k', 'auth' => 'a'],
        ])->assertOk();

        $this->assertDatabaseHas('push_subscriptions', ['user_id' => $user->id]);
    }
}
