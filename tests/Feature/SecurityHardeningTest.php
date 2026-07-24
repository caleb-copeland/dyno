<?php

namespace Tests\Feature;

use App\Livewire\ScheduleBuilder;
use App\Models\Schedule;
use App\Models\User;
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

    public function test_save_rejects_an_unknown_focus_area(): void
    {
        $user = User::factory()->create();

        // A junk focus string would poison the FocusArea enum cast on every
        // later read (dashboard, reminder cron) if it reached the database.
        Livewire::actingAs($user)
            ->test(ScheduleBuilder::class)
            ->set('current', [['focus' => 'evil', 'day' => 0]])
            ->set('generated', true)
            ->call('save')
            ->assertSet('saved', false);

        $this->assertSame(0, Schedule::where('user_id', $user->id)->count());
    }

    public function test_save_rejects_an_out_of_range_day(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ScheduleBuilder::class)
            ->set('current', [['focus' => 'grip', 'day' => 42]])
            ->set('generated', true)
            ->call('save')
            ->assertSet('saved', false);

        $this->assertSame(0, Schedule::where('user_id', $user->id)->count());
    }

    public function test_generate_clamps_absurd_frequencies(): void
    {
        $user = User::factory()->create();

        // Unclamped, this would try to build a 500k-element sessions array.
        $component = Livewire::actingAs($user)
            ->test(ScheduleBuilder::class)
            ->set('frequencies', ['grip' => 500000, 'bogus_area' => 3])
            ->call('generate')
            ->assertSet('generated', true);

        $frequencies = $component->get('frequencies');
        $this->assertLessThanOrEqual(7, $frequencies['grip']);
        $this->assertArrayNotHasKey('bogus_area', $frequencies);
    }

    public function test_generate_discards_invalid_day_numbers(): void
    {
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(ScheduleBuilder::class)
            ->set('trainingDays', [0, 1, '99', -5])
            ->set('climbingDays', [77])
            ->call('generate');

        $this->assertSame([0, 1], $component->get('trainingDays'));
        $this->assertSame([], $component->get('climbingDays'));
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
