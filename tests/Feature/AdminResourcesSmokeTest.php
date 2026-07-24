<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workout;
use Database\Seeders\LibrarySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Renders the Filament resource pages as an admin — catches form/table/schema
 * misconfiguration (e.g. the Workout exercise repeater) that only surfaces at
 * page-render time.
 */
class AdminResourcesSmokeTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'active' => true]);
    }

    public function test_resource_index_and_create_pages_render(): void
    {
        $admin = $this->admin();

        $urls = [
            '/admin/exercises',
            '/admin/exercises/create',
            '/admin/workouts',
            '/admin/workouts/create',
            '/admin/invites',
            '/admin/invites/create',
        ];

        foreach ($urls as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_workout_edit_page_renders_with_seeded_data(): void
    {
        $this->seed(LibrarySeeder::class);
        $admin = $this->admin();
        $workout = Workout::first();

        $this->actingAs($admin)
            ->get("/admin/workouts/{$workout->getKey()}/edit")
            ->assertOk();
    }

    public function test_member_is_forbidden_from_resource_pages(): void
    {
        $member = User::factory()->create(['role' => 'member', 'active' => true]);

        $this->actingAs($member)->get('/admin/exercises')->assertForbidden();
    }
}
