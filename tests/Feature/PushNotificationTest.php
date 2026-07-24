<?php

namespace Tests\Feature;

use App\Models\PushSubscription;
use App\Models\Schedule;
use App\Models\User;
use App\Services\WebPushSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PushNotificationTest extends TestCase
{
    use RefreshDatabase;

    private function subscribePayload(string $endpoint = 'https://push.example.com/abc'): array
    {
        return [
            'endpoint' => $endpoint,
            'keys' => ['p256dh' => 'BPk_key', 'auth' => 'authsecret'],
        ];
    }

    public function test_key_endpoint_404s_when_push_is_disabled(): void
    {
        config(['webpush.enabled' => false]);
        $user = User::factory()->create();

        $this->actingAs($user)->getJson(route('api.push.key'))->assertNotFound();
    }

    public function test_key_endpoint_returns_public_key_when_enabled(): void
    {
        config(['webpush.enabled' => true, 'webpush.vapid.public_key' => 'PUBKEY123']);
        $user = User::factory()->create();

        $this->actingAs($user)->getJson(route('api.push.key'))
            ->assertOk()->assertJson(['key' => 'PUBKEY123']);
    }

    public function test_guest_cannot_subscribe(): void
    {
        $this->postJson(route('api.push.subscribe'), $this->subscribePayload())->assertUnauthorized();
    }

    public function test_member_can_subscribe_and_resubscribing_is_idempotent(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('api.push.subscribe'), $this->subscribePayload())
            ->assertOk()->assertJson(['ok' => true]);

        // Same endpoint again → updated, not duplicated.
        $this->actingAs($user)->postJson(route('api.push.subscribe'), $this->subscribePayload())->assertOk();

        $this->assertSame(1, PushSubscription::count());
        $this->assertDatabaseHas('push_subscriptions', ['user_id' => $user->id]);
    }

    public function test_member_can_unsubscribe(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->postJson(route('api.push.subscribe'), $this->subscribePayload());

        $this->actingAs($user)->deleteJson(route('api.push.unsubscribe'), ['endpoint' => 'https://push.example.com/abc'])
            ->assertOk();

        $this->assertSame(0, PushSubscription::count());
    }

    // ---- Reminder targeting ----

    private function spySender(): object
    {
        $spy = new class extends WebPushSender
        {
            public int $sent = 0;

            public function send(\App\Models\PushSubscription $sub, array $payload): bool
            {
                $this->sent++;

                return true;
            }
        };
        $this->app->instance(WebPushSender::class, $spy);

        return $spy;
    }

    private function scheduleTrainingToday(User $user): Schedule
    {
        $todayNum = now()->dayOfWeekIso - 1;
        $schedule = Schedule::create(['user_id' => $user->id, 'active' => true]);
        $schedule->sessions()->create(['day_of_week' => $todayNum, 'focus_area' => 'grip', 'position' => 0]);

        return $schedule;
    }

    private function subscribeUser(User $user): void
    {
        PushSubscription::create([
            'user_id' => $user->id,
            'endpoint_hash' => PushSubscription::hashEndpoint('e'.$user->id),
            'endpoint' => 'e'.$user->id,
            'public_key' => 'k',
            'auth_token' => 'a',
        ]);
    }

    public function test_reminder_is_sent_to_a_subscribed_user_training_today(): void
    {
        $spy = $this->spySender();
        $user = User::factory()->create();
        $this->scheduleTrainingToday($user);
        $this->subscribeUser($user);

        $this->artisan('app:send-reminders')->assertSuccessful();

        $this->assertSame(1, $spy->sent);
    }

    public function test_no_reminder_without_a_subscription(): void
    {
        $spy = $this->spySender();
        $user = User::factory()->create();
        $this->scheduleTrainingToday($user); // no subscription

        $this->artisan('app:send-reminders')->assertSuccessful();

        $this->assertSame(0, $spy->sent);
    }

    public function test_no_reminder_when_nothing_is_scheduled_today(): void
    {
        $spy = $this->spySender();
        $user = User::factory()->create();
        // Session on a different day.
        $schedule = Schedule::create(['user_id' => $user->id, 'active' => true]);
        $other = (now()->dayOfWeekIso - 1 + 3) % 7;
        $schedule->sessions()->create(['day_of_week' => $other, 'focus_area' => 'grip', 'position' => 0]);
        $this->subscribeUser($user);

        $this->artisan('app:send-reminders')->assertSuccessful();

        $this->assertSame(0, $spy->sent);
    }
}
