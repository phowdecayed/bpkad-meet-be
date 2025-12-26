<?php

namespace Tests\Feature;

use App\Enums\MeetingStatus;
use App\Models\Meeting;
use App\Models\User;
use App\Models\ZoomMeeting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class MeetingStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_organizer_can_update_meeting_status_manually()
    {
        Permission::create(['name' => 'edit meetings']);
        $user = User::factory()->create();
        $user->givePermissionTo('edit meetings');

        $meeting = Meeting::factory()->create(['organizer_id' => $user->id, 'status' => MeetingStatus::SCHEDULED]);

        $this->actingAs($user);

        // Update to STARTED
        $response = $this->patchJson("/api/meetings/{$meeting->id}", [
            'status' => 'started',
            'topic' => $meeting->topic, // required in some contexts or validation?
        ]);

        $response->assertOk();
        $this->assertEquals(MeetingStatus::STARTED, $meeting->refresh()->status);

        // Update to FINISHED
        $response = $this->patchJson("/api/meetings/{$meeting->id}", [
            'status' => 'finished',
            'topic' => $meeting->topic,
        ]);

        $response->assertOk();
        $this->assertEquals(MeetingStatus::FINISHED, $meeting->refresh()->status);
    }

    public function test_invalid_status_is_rejected()
    {
        Permission::create(['name' => 'edit meetings']);
        $user = User::factory()->create();
        $user->givePermissionTo('edit meetings');

        $meeting = Meeting::factory()->create(['organizer_id' => $user->id]);

        $this->actingAs($user);

        $response = $this->patchJson("/api/meetings/{$meeting->id}", [
            'status' => 'invalid-status',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['status']);
    }

    public function test_zoom_sync_updates_parent_meeting_status()
    {
        Permission::create(['name' => 'edit meetings']);
        $user = User::factory()->create();
        $user->givePermissionTo('edit meetings');

        // Verify user is organizer
        $meeting = Meeting::factory()->create(['status' => MeetingStatus::SCHEDULED, 'organizer_id' => $user->id]);

        // Create Zoom Meeting linked to it
        $setting = \App\Models\Setting::factory()->create(['group' => 'zoom', 'payload' => ['account_id' => 'a', 'client_id' => 'b', 'client_secret' => 'c']]);
        $zoomMeeting = ZoomMeeting::create([
            'meeting_id' => $meeting->id,
            'setting_id' => $setting->id,
            'zoom_id' => '123456',
            'uuid' => 'uuid-123',
            'start_url' => 'http://start',
            'join_url' => 'http://join',
            'status' => 'waiting', // Initially waiting
            'settings' => [],
        ]);

        $this->actingAs($user);

        // Mock ZoomService behavior
        $this->mock(\App\Services\ZoomService::class, function ($mock) {
            $mock->shouldReceive('setCredentials');
            $mock->shouldReceive('getRecordings')->andReturn(new \Illuminate\Http\Client\Response(new \GuzzleHttp\Psr7\Response(200, [], json_encode([]))));
            $mock->shouldReceive('getMeetingSummary')->andReturn(new \Illuminate\Http\Client\Response(new \GuzzleHttp\Psr7\Response(200, [], json_encode(['summary_details' => 'summary']))));
        });

        // 1. Manually update ZoomMeeting status to 'started' to simulate what happens *after* a webhook or fetch
        // In the controller logic, syncZoomData actually fetches recordings/summary.
        // Wait, the controller logic uses `$zoomMeeting->status`. Does it UPDATE logic from API?
        // Checking controller: It does NOT fetch getMeeting() to update status. It only fetches recordings/summary.
        // It relies on $zoomMeeting->status already being up to date (e.g. via separate updateMeeting call or Webhook).
        // BUT my sync logic reads `$zoomMeeting->status`.
        // So I should simulate that the local zoom meeting status IS 'started' (maybe updated by a webhook or previous sync).
        // Let's manually set it to started before calling sync to verify the trigger works.

        $zoomMeeting->update(['status' => 'started']);

        $response = $this->getJson('/api/zoom/meetings/123456/sync');

        $response->assertOk();

        // Assert Parent Meeting Status is now STARTED
        $this->assertEquals(MeetingStatus::STARTED, $meeting->refresh()->status);
    }
}
