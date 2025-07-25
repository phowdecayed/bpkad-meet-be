<?php

namespace Tests\Feature;

use App\Models\Meeting;
use App\Models\Setting;
use App\Models\User;
use App\Models\ZoomMeeting;
use App\Services\ZoomService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MeetingCreationTest extends TestCase
{
    use RefreshDatabase;

    protected $organizer;
    protected $zoomSetting1;
    protected $zoomSetting2;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles and permissions
        Role::create(['name' => 'admin']);
        $organizerRole = Role::create(['name' => 'organizer']);
        $permission = Permission::create(['name' => 'create meetings']);
        $organizerRole->givePermissionTo($permission);

        // Create a user with the 'organizer' role
        $this->organizer = User::factory()->create();
        $this->organizer->assignRole($organizerRole);

        // Create mock Zoom credentials
        $this->zoomSetting1 = Setting::factory()->create([
            'name' => 'Zoom Account 1',
            'group' => 'zoom',
            'payload' => [
                'client_id' => 'test_client_id_1',
                'client_secret' => 'test_client_secret_1',
                'account_id' => 'test_account_id_1',
            ],
        ]);

        $this->zoomSetting2 = Setting::factory()->create([
            'name' => 'Zoom Account 2',
            'group' => 'zoom',
            'payload' => [
                'client_id' => 'test_client_id_2',
                'client_secret' => 'test_client_secret_2',
                'account_id' => 'test_account_id_2',
            ],
        ]);

        // Mock the Zoom API responses
        Http::fake([
            'https://zoom.us/oauth/token' => Http::response([
                'access_token' => 'fake-access-token',
                'expires_in' => 3600,
            ]),
            'https://api.zoom.us/v2/*' => Http::response([
                'id' => '123456789',
                'uuid' => 'abcdefg==',
                'host_id' => 'host_id_123',
                'host_email' => 'host@example.com',
                'topic' => 'Test Meeting',
                'type' => 2,
                'status' => 'waiting',
                'start_time' => now()->addHour()->toIso8601String(),
                'duration' => 60,
                'timezone' => 'UTC',
                'created_at' => now()->toIso8601String(),
                'start_url' => 'https://zoom.us/s/123456789',
                'join_url' => 'https://zoom.us/j/123456789',
                'password' => '123456',
                'settings' => [],
            ], 201),
        ]);
    }

    #[Test]
    public function it_uses_the_next_available_credential_when_the_first_is_busy()
    {
        // Arrange: Create 2 active meetings for the first credential
        for ($i = 0; $i < 2; $i++) {
            $meeting = Meeting::factory()->create(['organizer_id' => $this->organizer->id]);
            ZoomMeeting::factory()->create([
                'meeting_id' => $meeting->id,
                'setting_id' => $this->zoomSetting1->id,
                'start_time' => now()->subMinutes(30),
                'duration' => 60,
            ]);
        }

        // Act: Attempt to create a new online meeting
        $response = $this->actingAs($this->organizer)->postJson('/api/meetings', [
            'topic' => 'Meeting on Second Credential',
            'start_time' => now()->addDay(),
            'duration' => 60,
            'type' => 'online',
        ]);

        // Assert
        $response->assertStatus(201);
        $this->assertDatabaseHas('meetings', ['topic' => 'Meeting on Second Credential']);
        $newMeeting = Meeting::where('topic', 'Meeting on Second Credential')->first();
        
        // Verify it's on the second credential
        $this->assertNotNull($newMeeting->zoomMeeting);
        $this->assertEquals($this->zoomSetting2->id, $newMeeting->zoomMeeting->setting_id);
    }

    #[Test]
    public function it_fails_when_all_credentials_are_at_maximum_capacity()
    {
        // Arrange: Create 2 active meetings for BOTH credentials
        foreach ([$this->zoomSetting1, $this->zoomSetting2] as $setting) {
            for ($i = 0; $i < 2; $i++) {
                $meeting = Meeting::factory()->create(['organizer_id' => $this->organizer->id]);
                ZoomMeeting::factory()->create([
                    'meeting_id' => $meeting->id,
                    'setting_id' => $setting->id,
                    'start_time' => now()->subMinutes(30),
                    'duration' => 60,
                ]);
            }
        }

        // Act: Attempt to create a new online meeting
        $response = $this->actingAs($this->organizer)->postJson('/api/meetings', [
            'topic' => 'This Should Fail',
            'start_time' => now()->addDay(),
            'duration' => 60,
            'type' => 'online',
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('zoom_api');
        $response->assertJsonPath('errors.zoom_api.0', 'All Zoom accounts are currently busy. Please try again later.');
    }

    #[Test]
    public function it_fails_when_no_zoom_credentials_are_configured()
    {
        // Arrange: Delete all zoom settings
        Setting::where('group', 'zoom')->delete();

        // Act: Attempt to create a new online meeting
        $response = $this->actingAs($this->organizer)->postJson('/api/meetings', [
            'topic' => 'This Should Also Fail',
            'start_time' => now()->addDay(),
            'duration' => 60,
            'type' => 'online',
        ]);

        // Assert
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('zoom_api');
        $response->assertJsonPath('errors.zoom_api.0', 'Zoom integration settings are not configured.');
    }
}