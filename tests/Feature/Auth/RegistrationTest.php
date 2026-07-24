<?php

namespace Tests\Feature\Auth;

use App\Models\Invite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_404s_without_a_token(): void
    {
        $this->get('/register')->assertNotFound();
    }

    public function test_registration_screen_404s_with_an_invalid_token(): void
    {
        $this->get('/register?token=not-a-real-token')->assertNotFound();
    }

    public function test_registration_screen_renders_with_a_valid_token(): void
    {
        [, $raw] = Invite::issue('invitee@example.com');

        $this->get('/register?token='.$raw)->assertOk();
    }

    public function test_expired_invite_is_rejected(): void
    {
        [, $raw] = Invite::issue('invitee@example.com', expiresAt: now()->subMinute());

        $this->get('/register?token='.$raw)->assertNotFound();
    }

    public function test_invited_user_can_register(): void
    {
        [$invite, $raw] = Invite::issue('invitee@example.com');

        $response = $this->post('/register', [
            'token' => $raw,
            'name' => 'Test User',
            'email' => 'invitee@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        $user = User::where('email', 'invitee@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('member', $user->role);
        $this->assertTrue($user->active);

        $this->assertNotNull($invite->fresh()->used_at);
        $this->assertSame($user->id, $invite->fresh()->user_id);
    }

    public function test_registration_email_must_match_the_invite(): void
    {
        [, $raw] = Invite::issue('invitee@example.com');

        $this->post('/register', [
            'token' => $raw,
            'name' => 'Test User',
            'email' => 'someone-else@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'someone-else@example.com']);
    }

    public function test_an_invite_cannot_be_used_twice(): void
    {
        [, $raw] = Invite::issue('invitee@example.com');

        $this->post('/register', [
            'token' => $raw,
            'name' => 'First',
            'email' => 'invitee@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        // Log out and attempt to reuse the same token for a new account.
        $this->post('/logout');

        $this->post('/register', [
            'token' => $raw,
            'name' => 'Second',
            'email' => 'invitee@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertSame(1, User::where('email', 'invitee@example.com')->count());
    }
}
