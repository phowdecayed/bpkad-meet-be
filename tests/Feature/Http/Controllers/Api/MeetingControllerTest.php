<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\Meeting;
use App\Models\MeetingLocation;
use App\Models\User;
use App\Models\ZoomMeeting;
use App\Services\ZoomService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MeetingControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions
        $manageMeetingsPermission = Permission::create(['name' => 'manage meetings']);
        $deleteMeetingsPermission = Permission::create(['name' => 'delete meetings']);

        // Create a role and assign permissions
        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo($manageMeetingsPermission);
        $adminRole->givePermissionTo($deleteMeetingsPermission);

        // Create a user and assign the role
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole($adminRole);

        // Act as the admin user for all tests in this class
        $this->actingAs($this->adminUser);
    }

    #[Test]
    public function it_can_list_meetings()
    {
        Meeting::factory()->count(3)->create();
        $response = $this->getJson('/api/meetings');
        $response->assertOk()->assertJsonCount(3, 'data');
    }

    #[Test]
    public function it_can_show_a_meeting()
    {
        $meeting = Meeting::factory()->create();
        $response = $this->getJson("/api/meetings/{$meeting->id}");
        $response->assertOk()->assertJsonFragment(['id' => $meeting->id]);
    }

    #[Test]
    public function it_can_create_an_offline_meeting()
    {
        $location = MeetingLocation::factory()->create();
        $startTime = now()->addDays(5)->startOfSecond();
        $data = [
            'topic' => 'Offline Meeting Test',
            'start_time' => $startTime->toDateTimeString(),
            'duration' => 60,
            'type' => 'offline',
            'location_id' => $location->id,
        ];

        $response = $this->postJson('/api/meetings', $data);

        $response->assertStatus(201)->assertJsonFragment(['topic' => 'Offline Meeting Test']);
        $this->assertDatabaseHas('meetings', $data);
    }

    #[Test]
    public function it_can_create_an_online_meeting()
    {
        // Create a dummy setting for the test
        \App\Models\Setting::create([
            'name' => 'Test Zoom Account',
            'group' => 'zoom',
            'payload' => [
                'client_id' => 'test',
                'client_secret' => 'test',
                'account_id' => 'test',
            ],
        ]);

        Http::fake([
            'https://zoom.us/oauth/token' => Http::response(['access_token' => 'fake_token']),
            'https://api.zoom.us/v2/users/me/meetings' => Http::response(json_decode('{
                "uuid": "3SjLbv0IRgmY2LX0FzPJSg==",
                "id": 72093907398,
                "host_id": "LW5hOGSJRm-dbItnXqlNeQ",
                "host_email": "rachmatsharyadi@gmail.com",
                "topic": "Test Meeting",
                "type": 2,
                "status": "waiting",
                "start_time": "2025-07-24T12:21:11Z",
                "duration": 30,
                "timezone": "Asia/Jakarta",
                "created_at": "2025-07-23T14:41:31Z",
                "start_url": "https://us04web.zoom.us/s/72093907398?zak=...",
                "join_url": "https://us04web.zoom.us/j/72093907398?pwd=...",
                "password": "X4rfxX",
                "settings": { "host_video": true }
            }', true), 201)
        ]);

        $startTime = now()->addDays(5)->startOfSecond();
        $data = [
            'topic' => 'Online Meeting Test',
            'start_time' => $startTime->toDateTimeString(),
            'duration' => 60,
            'type' => 'online',
        ];

        $response = $this->postJson('/api/meetings', $data);

        $response->assertStatus(201)
            ->assertJsonFragment(['topic' => 'Online Meeting Test'])
            ->assertJsonPath('data.zoom_meeting.zoom_id', 72093907398);

        $this->assertDatabaseHas('meetings', ['topic' => 'Online Meeting Test']);
        $this->assertDatabaseCount('zoom_meetings', 1);
    }


    #[Test]
    public function it_can_update_a_meeting()
    {
        $meeting = Meeting::factory()->create();
        $updateData = ['topic' => 'Updated Meeting Topic'];

        $response = $this->patchJson("/api/meetings/{$meeting->id}", $updateData);

        $response->assertOk()->assertJsonFragment($updateData);
        $this->assertDatabaseHas('meetings', $updateData);
    }

    #[Test]
    public function it_can_delete_an_offline_meeting()
    {
        $meeting = Meeting::factory()->create(['type' => 'offline']);

        $response = $this->deleteJson("/api/meetings/{$meeting->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('meetings', ['id' => $meeting->id]);
    }

    #[Test]
    public function it_can_delete_an_online_meeting_and_calls_zoom_service()
    {
        Http::fake([
            'https://zoom.us/oauth/token' => Http::response(['access_token' => 'fake_token']),
            'https://api.zoom.us/v2/meetings/*' => Http::response(null, 204)
        ]);

        $meeting = Meeting::factory()->create(['type' => 'online']);
        ZoomMeeting::factory()->create(['meeting_id' => $meeting->id]);

        $response = $this->deleteJson("/api/meetings/{$meeting->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('meetings', ['id' => $meeting->id]);
    }
}
