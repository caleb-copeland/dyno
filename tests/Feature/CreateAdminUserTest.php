<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateAdminUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_user_is_actually_promoted_to_active_admin(): void
    {
        // Regression: role & active are not mass-assignable, so an update()
        // call silently discarded them and the "promotion" was a no-op.
        $user = User::factory()->create(['role' => 'member', 'active' => false]);

        $this->artisan('app:create-admin')
            ->expectsQuestion('Name', 'Irrelevant')
            ->expectsQuestion('Email', strtoupper($user->email))
            ->assertSuccessful();

        $user->refresh();
        $this->assertSame('admin', $user->role);
        $this->assertTrue($user->active);
    }
}
