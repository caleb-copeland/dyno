<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeactivatedUserAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_deactivated_user_with_an_existing_session_is_logged_out(): void
    {
        // Deactivation must not only block new logins (LoginRequest) — it must
        // also terminate sessions that already exist, and cover remember-me
        // re-authentication, which never passes through Auth::attempt().
        $user = User::factory()->create(['active' => false]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_active_user_session_is_unaffected(): void
    {
        $user = User::factory()->create(['active' => true]);

        $this->actingAs($user)->get('/dashboard')->assertOk();
        $this->assertAuthenticated();
    }
}
