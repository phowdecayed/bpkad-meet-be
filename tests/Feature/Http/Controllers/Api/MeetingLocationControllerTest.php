<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\MeetingLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MeetingLocationControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $adminUser;

    protected User $basicUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a role and assign the necessary permission
        $editMeetingsPermission = Permission::create(['name' => 'edit meetings']);
        $adminRole = Role::create(['name' => 'admin'])->givePermissionTo($editMeetingsPermission);
        $userRole = Role::create(['name' => 'user']);

        // Create a user and assign the role
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole($adminRole);

        $this->basicUser = User::factory()->create();
        $this->basicUser->assignRole($userRole);
    }

    #[Test]
    public function it_can_list_meeting_locations()
    {
        MeetingLocation::factory()->count(3)->create();

        $response = $this->actingAs($this->adminUser)->getJson('/api/meeting-locations');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    #[Test]
    public function non_admin_cannot_list_meeting_locations()
    {
        $response = $this->actingAs($this->basicUser)->getJson('/api/meeting-locations');
        $response->assertStatus(403);
    }

    #[Test]
    public function it_can_create_a_meeting_location()
    {
        $data = [
            'name' => $this->faker->company,
            'address' => $this->faker->address,
            'room_name' => 'Conference Room '.$this->faker->randomLetter,
            'capacity' => $this->faker->numberBetween(10, 100),
        ];

        $response = $this->actingAs($this->adminUser)->postJson('/api/meeting-locations', $data);

        $response->assertStatus(201)
            ->assertJsonFragment($data);

        $this->assertDatabaseHas('meeting_locations', $data);
    }

    #[Test]
    public function it_can_show_a_meeting_location()
    {
        $location = MeetingLocation::factory()->create();

        $response = $this->actingAs($this->adminUser)->getJson("/api/meeting-locations/{$location->id}");

        $response->assertOk()
            ->assertJsonFragment(['id' => $location->id]);
    }

    #[Test]
    public function it_can_update_a_meeting_location()
    {
        $location = MeetingLocation::factory()->create();
        $updateData = ['name' => 'Updated Location Name'];

        $response = $this->actingAs($this->adminUser)->patchJson("/api/meeting-locations/{$location->id}", $updateData);

        $response->assertOk()
            ->assertJsonFragment($updateData);

        $this->assertDatabaseHas('meeting_locations', $updateData);
    }

    #[Test]
    public function it_can_delete_a_meeting_location()
    {
        $location = MeetingLocation::factory()->create();

        $response = $this->actingAs($this->adminUser)->deleteJson("/api/meeting-locations/{$location->id}");

        $response->assertStatus(200); // Should be 200 for successful deletion message

        $this->assertDatabaseMissing('meeting_locations', ['id' => $location->id]);
    }
}
