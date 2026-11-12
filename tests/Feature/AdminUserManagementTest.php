<?php

namespace Tests\Feature;

use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'active' => true]);
    }

    public function test_edit_page_renders(): void
    {
        $member = User::factory()->create(['role' => 'member', 'active' => true]);

        $this->actingAs($this->admin())
            ->get("/admin/users/{$member->getKey()}/edit")
            ->assertOk();
    }

    public function test_admin_can_change_another_users_role_and_access(): void
    {
        $member = User::factory()->create(['role' => 'member', 'active' => true]);

        Livewire::actingAs($this->admin())
            ->test(EditUser::class, ['record' => $member->getKey()])
            ->fillForm(['role' => 'admin', 'active' => false])
            ->call('save')
            ->assertHasNoFormErrors();

        $member->refresh();
        $this->assertSame('admin', $member->role);
        $this->assertFalse($member->active);
    }

    public function test_admin_cannot_demote_or_deactivate_themselves(): void
    {
        $admin = $this->admin();

        Livewire::actingAs($admin)
            ->test(EditUser::class, ['record' => $admin->getKey()])
            ->fillForm(['name' => 'Renamed', 'role' => 'member', 'active' => false])
            ->call('save');

        $admin->refresh();
        $this->assertSame('Renamed', $admin->name);
        $this->assertSame('admin', $admin->role);
        $this->assertTrue($admin->active);
    }

    public function test_deactivate_action_toggles_access(): void
    {
        $member = User::factory()->create(['role' => 'member', 'active' => true]);

        Livewire::actingAs($this->admin())
            ->test(ListUsers::class)
            ->callTableAction('toggleActive', $member);

        $this->assertFalse($member->refresh()->active);
    }

    public function test_deactivate_action_is_hidden_for_self(): void
    {
        $admin = $this->admin();

        Livewire::actingAs($admin)
            ->test(ListUsers::class)
            ->assertTableActionHidden('toggleActive', $admin);
    }

    public function test_member_cannot_reach_users_page(): void
    {
        $member = User::factory()->create(['role' => 'member', 'active' => true]);

        $this->actingAs($member)->get('/admin/users')->assertForbidden();
    }
}
