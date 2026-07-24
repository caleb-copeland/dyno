<?php

namespace Tests\Feature;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelAccessTest extends TestCase
{
    use RefreshDatabase;

    private function panel()
    {
        return Filament::getPanel('admin');
    }

    public function test_active_admin_can_access_panel(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'active' => true]);

        $this->assertTrue($admin->canAccessPanel($this->panel()));
    }

    public function test_member_cannot_access_panel(): void
    {
        $member = User::factory()->create(['role' => 'member', 'active' => true]);

        $this->assertFalse($member->canAccessPanel($this->panel()));
    }

    public function test_inactive_admin_cannot_access_panel(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'active' => false]);

        $this->assertFalse($admin->canAccessPanel($this->panel()));
    }

    public function test_deactivated_user_cannot_log_in(): void
    {
        $user = User::factory()->create(['active' => false, 'password' => bcrypt('password')]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
