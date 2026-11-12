<?php

namespace Tests\Feature;

use App\Filament\Resources\Invites\Pages\CreateInvite;
use App\Filament\Resources\Invites\Pages\ListInvites;
use App\Models\Invite;
use App\Models\User;
use App\Notifications\InviteNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class InviteEmailTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'active' => true]);
    }

    public function test_creating_an_invite_emails_the_link(): void
    {
        Notification::fake();

        Livewire::actingAs($this->admin())
            ->test(CreateInvite::class)
            ->fillForm(['email' => 'climber@example.com', 'expires_at' => now()->addDays(7)])
            ->call('create')
            ->assertHasNoFormErrors();

        Notification::assertSentOnDemand(
            InviteNotification::class,
            fn ($notification, array $channels, AnonymousNotifiable $notifiable) => $notifiable->routes['mail'] === 'climber@example.com',
        );
    }

    public function test_emailed_link_registers_against_the_invite(): void
    {
        Notification::fake();

        Livewire::actingAs($this->admin())
            ->test(CreateInvite::class)
            ->fillForm(['email' => 'climber@example.com', 'expires_at' => now()->addDays(7)])
            ->call('create');

        Notification::assertSentOnDemand(InviteNotification::class, function ($notification, $channels, $notifiable) {
            $url = $notification->toMail($notifiable)->actionUrl;
            auth()->logout();
            $this->get($url)->assertOk();

            return true;
        });
    }

    public function test_resend_emails_a_fresh_link(): void
    {
        Notification::fake();
        [$invite] = Invite::issue('climber@example.com');

        Livewire::actingAs($this->admin())
            ->test(ListInvites::class)
            ->callTableAction('regenerate', $invite);

        Notification::assertSentOnDemandTimes(InviteNotification::class, 1);
    }
}
