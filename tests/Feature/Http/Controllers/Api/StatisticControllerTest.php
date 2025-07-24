<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StatisticControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $adminUser;
    protected User $basicUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions
        $manageMeetingsPermission = Permission::create(['name' => 'manage meetings']);

        // Create roles
        $adminRole = Role::create(['name' => 'admin'])->givePermissionTo($manageMeetingsPermission);
        $userRole = Role::create(['name' => 'user']);

        // Create users
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole($adminRole);

        $this->basicUser = User::factory()->create();
        $this->basicUser->assignRole($userRole);
    }

    public function test_admin_can_view_statistics()
    {
        $response = $this->actingAs($this->adminUser)->getJson('/api/statistics/dashboard');
        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'overview' => ['total_meetings', 'average_duration_minutes', 'meetings_this_month'],
                    'meeting_trends' => ['by_type'],
                    'leaderboards' => ['top_organizers', 'top_locations'],
                ]
            ]);
    }

    public function test_non_admin_cannot_view_statistics()
    {
        $response = $this->actingAs($this->basicUser)->getJson('/api/statistics/dashboard');
        $response->assertStatus(403);
    }
}