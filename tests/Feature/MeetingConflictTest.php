<?php

namespace Tests\Feature;

use App\Models\Meeting;
use App\Models\MeetingLocation;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MeetingConflictTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected $location;

    protected function setUp(): void
    {
        parent::setUp();

        // Fake the Zoom API HTTP calls for all tests in this file
        Http::fake([
            'https://zoom.us/oauth/token' => Http::response([
                'access_token' => 'fake-access-token',
                'expires_in' => 3600,
            ]),
            'https://api.zoom.us/v2/*' => Http::response([
                'uuid' => 'fake-uuid-123',
                'id' => '123456789',
                'host_id' => 'fake-host-id',
                'host_email' => 'fake@example.com',
                'topic' => 'Test Meeting',
                'type' => 2,
                'status' => 'waiting',
                'start_time' => now()->toIso8601String(),
                'duration' => 60,
                'timezone' => 'UTC',
                'created_at' => now()->toIso8601String(),
                'start_url' => 'https://zoom.us/s/123456789',
                'join_url' => 'https://zoom.us/j/123456789',
                'password' => '123456',
                'settings' => [
                    'host_video' => true,
                    'participant_video' => true,
                    'join_before_host' => true,
                    'mute_upon_entry' => true,
                    'waiting_room' => false,
                ],
            ]),
        ]);

        // Create permissions
        $createMeetingsPermission = Permission::create(['name' => 'create meetings']);
        $editMeetingsPermission = Permission::create(['name' => 'edit meetings']);

        // Create a role and assign permissions
        $role = Role::create(['name' => 'organizer']);
        $role->givePermissionTo($createMeetingsPermission);
        $role->givePermissionTo($editMeetingsPermission);

        // Create a user and assign the role
        $this->user = User::factory()->create();
        $this->user->assignRole($role);

        // Create a meeting location
        $this->location = MeetingLocation::factory()->create();

        // Create dummy Zoom settings for tests that need it
        Setting::create([
            'name' => 'Zoom Credentials',
            'group' => 'zoom',
            'payload' => [
                'client_id' => 'test_client_id',
                'client_secret' => 'test_client_secret',
                'account_id' => 'test_account_id',
            ],
        ]);
    }

    /**
     * Test that a meeting cannot be created if its time slot overlaps with an existing meeting at the same location.
     */
    public function test_cannot_create_meeting_with_overlapping_time_at_same_location()
    {
        // Arrange: Create an existing meeting
        Meeting::factory()->create([
            'location_id' => $this->location->id,
            'start_time' => '2025-08-01 10:00:00',
            'duration' => 60, // 10:00 - 11:00
            'type' => 'offline',
        ]);

        // Act: Attempt to create a new meeting that overlaps with the existing one
        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/meetings', [
            'topic' => 'Conflicting Meeting',
            'start_time' => '2025-08-01 10:30:00', // Overlaps
            'duration' => 60,
            'type' => 'offline',
            'location_id' => $this->location->id,
        ]);

        // Assert: Check for a validation error
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['start_time']);
    }

    /**
     * Test that a meeting can be created if its time slot is adjacent to another meeting at the same location.
     */
    public function test_can_create_meeting_immediately_after_another_at_same_location()
    {
        // Arrange: Create an existing meeting
        Meeting::factory()->create([
            'location_id' => $this->location->id,
            'start_time' => '2025-08-01 10:00:00',
            'duration' => 60, // Ends at 11:00
            'type' => 'offline',
        ]);

        // Act: Attempt to create a new meeting that starts exactly when the other ends
        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/meetings', [
            'topic' => 'Adjacent Meeting',
            'start_time' => '2025-08-01 11:00:00', // Starts right after
            'duration' => 60,
            'type' => 'offline',
            'location_id' => $this->location->id,
        ]);

        // Assert: Check for a successful creation
        $response->assertStatus(201);
        $this->assertDatabaseHas('meetings', ['topic' => 'Adjacent Meeting']);
    }

    /**
     * Test that a meeting can be created at the same time as another if the locations are different.
     */
    public function test_can_create_meeting_at_same_time_in_different_location()
    {
        // Arrange: Create an existing meeting in the first location
        Meeting::factory()->create([
            'location_id' => $this->location->id,
            'start_time' => '2025-08-01 10:00:00',
            'duration' => 60,
            'type' => 'offline',
        ]);

        // Create a second location
        $differentLocation = MeetingLocation::factory()->create();

        // Act: Attempt to create a new meeting at the same time but in the different location
        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/meetings', [
            'topic' => 'Parallel Meeting',
            'start_time' => '2025-08-01 10:00:00',
            'duration' => 60,
            'type' => 'offline',
            'location_id' => $differentLocation->id,
        ]);

        // Assert: Check for a successful creation
        $response->assertStatus(201);
        $this->assertDatabaseHas('meetings', ['topic' => 'Parallel Meeting']);
    }

    /**
     * Test that an online meeting can be created even if an offline meeting is happening at the same time.
     */
    public function test_can_create_online_meeting_during_offline_meeting()
    {
        // Arrange: Create an existing offline meeting
        Meeting::factory()->create([
            'location_id' => $this->location->id,
            'start_time' => '2025-08-01 10:00:00',
            'duration' => 60,
            'type' => 'offline',
        ]);

        // Act: Attempt to create an online meeting at the same time (no location specified)
        $response = $this->actingAs($this->user, 'sanctum')->postJson('/api/meetings', [
            'topic' => 'Online Meeting',
            'start_time' => '2025-08-01 10:00:00',
            'duration' => 60,
            'type' => 'online',
            'location_id' => null, // No physical location
        ]);

        // Assert: Check for a successful creation
        $response->assertStatus(201);
        $this->assertDatabaseHas('meetings', ['topic' => 'Online Meeting']);
    }

    /**
     * Test that a meeting cannot be updated to a time that conflicts with another meeting.
     */
    public function test_cannot_update_meeting_to_conflict_with_another()
    {
        // Arrange: Create two meetings
        $meetingToUpdate = Meeting::factory()->create([
            'location_id' => $this->location->id,
            'start_time' => '2025-08-01 09:00:00',
            'duration' => 60,
            'type' => 'offline',
        ]);

        $existingMeeting = Meeting::factory()->create([
            'location_id' => $this->location->id,
            'start_time' => '2025-08-01 10:00:00',
            'duration' => 60,
            'type' => 'offline',
        ]);

        // Act: Attempt to update the first meeting to overlap with the second
        $response = $this->actingAs($this->user, 'sanctum')->putJson("/api/meetings/{$meetingToUpdate->id}", [
            'start_time' => '2025-08-01 10:30:00', // This new time conflicts with existingMeeting
        ]);

        // Assert: Check for a validation error
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['start_time']);
    }
}
