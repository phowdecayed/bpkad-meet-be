<?php

namespace Tests\Feature;

use App\Enums\MeetingType;
use App\Models\Meeting;
use App\Models\Setting;
use App\Models\User;
use App\Models\ZoomMeeting;
use App\Services\ZoomService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MeetingTypeTransitionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock the permissions
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    #[Test]
    public function it_creates_zoom_meeting_when_updating_from_offline_to_online()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('create meetings');
        $user->givePermissionTo('edit meetings');

        // Setup Zoom Settings
        Setting::create([
            'group' => 'zoom',
            'name' => 'Zoom Account 1',
            'payload' => [
                'client_id' => 'test_client_id',
                'client_secret' => 'test_client_secret',
                'account_id' => 'test_account_id',
            ],
        ]);

        // Mock Zoom Service to return success
        $this->mock(ZoomService::class, function ($mock) {
            $mock->shouldReceive('setCredentials')->times(1);
            $mock->shouldReceive('createMeeting')->once()->andReturn(
                new \Illuminate\Http\Client\Response(
                    new \GuzzleHttp\Psr7\Response(201, [], json_encode(['id' => 123456789, 'join_url' => 'http://zoom.us/j/123456789']))
                )
            );
        });

        // 1. Create Offline Meeting
        $meeting = Meeting::factory()->create([
            'organizer_id' => $user->id,
            'type' => MeetingType::OFFLINE,
            'start_time' => now()->addHour(),
            'duration' => 60,
        ]);

        $this->assertNull($meeting->zoomMeeting);

        // 2. Update to Online
        $response = $this->actingAs($user)->putJson("/api/meetings/{$meeting->id}", [
            'type' => MeetingType::ONLINE->value,
            'topic' => 'Updated to Online',
        ]);

        $response->assertOk();

        // 3. Verify Zoom Meeting Creation calls were made (via Mock)
        // Note: The assertion happens in the mock definition above.
    }

    #[Test]
    public function it_deletes_zoom_meeting_when_updating_from_online_to_offline()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('create meetings');
        $user->givePermissionTo('edit meetings');

        // Setup Zoom Settings
        $setting = Setting::create([
            'group' => 'zoom',
            'name' => 'Zoom Account 1',
            'payload' => [
                'client_id' => 'test_client_id',
                'client_secret' => 'test_client_secret',
                'account_id' => 'test_account_id',
            ],
        ]);

        // 1. Create Online Meeting with Zoom record
        $meeting = Meeting::factory()->create([
            'organizer_id' => $user->id,
            'type' => MeetingType::ONLINE,
            'start_time' => now()->addHour(),
            'duration' => 60,
        ]);

        ZoomMeeting::create([
            'meeting_id' => $meeting->id,
            'setting_id' => $setting->id,
            'zoom_id' => '123456789',
            'join_url' => 'http://zoom.us/j/123456789',
            'start_url' => 'http://zoom.us/s/123456789',
            'settings' => [],
        ]);

        // Mock Zoom Service to expect delete call
        $this->mock(ZoomService::class, function ($mock) {
            $mock->shouldReceive('setCredentials')->times(1);
            $mock->shouldReceive('deleteMeeting')->once()->with('123456789');
            $mock->shouldReceive('updateMeeting')->never(); // Explicitly verify this is NOT called
        });

        // 2. Update to Offline
        $response = $this->actingAs($user)->putJson("/api/meetings/{$meeting->id}", [
            'type' => MeetingType::OFFLINE->value,
            'topic' => 'Updated to Offline',
        ]);

        $response->assertOk();

        // 3. Verify assertion via Mock expecting deleteMeeting
    }
}
