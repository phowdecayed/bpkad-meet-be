<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\Meeting;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MeetingControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $adminUser;

    protected User $organizerUser;

    protected User $participantUser;

    protected User $unrelatedUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions
        $permissions = [
            'view meetings', 'create meetings', 'edit meetings', 'delete meetings',
        ];
        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles
        $adminRole = Role::create(['name' => 'admin'])->givePermissionTo($permissions);
        $userRole = Role::create(['name' => 'user'])->givePermissionTo('create meetings');

        // Create users
        $this->adminUser = User::factory()->create()->assignRole($adminRole);
        $this->organizerUser = User::factory()->create()->assignRole($userRole);
        $this->participantUser = User::factory()->create()->assignRole($userRole);
        $this->unrelatedUser = User::factory()->create()->assignRole($userRole);

        // Create a dummy setting for online meeting tests
        Setting::create([
            'name' => 'Test Zoom Account', 'group' => 'zoom',
            'payload' => ['client_id' => 'test', 'client_secret' => 'test', 'account_id' => 'test'],
        ]);
    }

    // Authorization Tests
    #[Test]
    public function organizer_can_update_their_own_meeting()
    {
        $meeting = Meeting::factory()->create(['organizer_id' => $this->organizerUser->id]);
        $response = $this->actingAs($this->organizerUser)->patchJson("/api/meetings/{$meeting->id}", ['topic' => 'New Topic']);
        $response->assertOk();
    }

    #[Test]
    public function organizer_can_delete_their_own_meeting()
    {
        $this->organizerUser->givePermissionTo('delete meetings');
        $meeting = Meeting::factory()->create(['organizer_id' => $this->organizerUser->id]);
        $response = $this->actingAs($this->organizerUser)->deleteJson("/api/meetings/{$meeting->id}");
        $response->assertOk();
    }

    #[Test]
    public function user_cannot_update_others_meeting()
    {
        $meeting = Meeting::factory()->create(['organizer_id' => $this->organizerUser->id]);
        $response = $this->actingAs($this->unrelatedUser)->patchJson("/api/meetings/{$meeting->id}", ['topic' => 'New Topic']);
        $response->assertStatus(403);
    }

    #[Test]
    public function user_cannot_delete_others_meeting()
    {
        $meeting = Meeting::factory()->create(['organizer_id' => $this->organizerUser->id]);
        $response = $this->actingAs($this->unrelatedUser)->deleteJson("/api/meetings/{$meeting->id}");
        $response->assertStatus(403);
    }

    #[Test]
    public function participant_can_view_meeting()
    {
        $meeting = Meeting::factory()->create(['organizer_id' => $this->organizerUser->id]);
        $meeting->participants()->attach($this->participantUser->id);
        $response = $this->actingAs($this->participantUser)->getJson("/api/meetings/{$meeting->id}");
        $response->assertOk();
    }

    #[Test]
    public function unrelated_user_cannot_view_meeting()
    {
        $meeting = Meeting::factory()->create(['organizer_id' => $this->organizerUser->id]);
        $response = $this->actingAs($this->unrelatedUser)->getJson("/api/meetings/{$meeting->id}");
        $response->assertStatus(403);
    }

    // List View Tests
    #[Test]
    public function admin_sees_all_meetings_in_list()
    {
        Meeting::factory()->count(5)->create();
        $response = $this->actingAs($this->adminUser)->getJson('/api/meetings');
        $response->assertOk()->assertJsonCount(5, 'data');
    }

    #[Test]
    public function user_sees_only_organized_and_invited_meetings()
    {
        // 2 organized, 1 invited to, 2 unrelated
        Meeting::factory()->create(['organizer_id' => $this->organizerUser->id]);
        Meeting::factory()->create(['organizer_id' => $this->organizerUser->id]);
        Meeting::factory()->create()->participants()->attach($this->organizerUser->id);
        Meeting::factory()->count(2)->create();

        $response = $this->actingAs($this->organizerUser)->getJson('/api/meetings');
        $response->assertOk()->assertJsonCount(3, 'data');
    }

    // Participant Management Tests
    #[Test]
    public function organizer_can_invite_participant()
    {
        $this->organizerUser->givePermissionTo('edit meetings');
        $meeting = Meeting::factory()->create(['organizer_id' => $this->organizerUser->id]);
        $response = $this->actingAs($this->organizerUser)->postJson("/api/meetings/{$meeting->id}/invite", ['user_id' => $this->participantUser->id]);
        $response->assertOk();
        $this->assertDatabaseHas('meeting_user', ['meeting_id' => $meeting->id, 'user_id' => $this->participantUser->id]);
    }

    #[Test]
    public function organizer_can_remove_participant()
    {
        $this->organizerUser->givePermissionTo('edit meetings');
        $meeting = Meeting::factory()->create(['organizer_id' => $this->organizerUser->id]);
        $meeting->participants()->attach($this->participantUser->id);
        $response = $this->actingAs($this->organizerUser)->deleteJson("/api/meetings/{$meeting->id}/participants/{$this->participantUser->id}");
        $response->assertOk();
        $this->assertDatabaseMissing('meeting_user', ['meeting_id' => $meeting->id, 'user_id' => $this->participantUser->id]);
    }

    #[Test]
    public function non_organizer_cannot_invite_participant()
    {
        $meeting = Meeting::factory()->create(['organizer_id' => $this->organizerUser->id]);
        $response = $this->actingAs($this->unrelatedUser)->postJson("/api/meetings/{$meeting->id}/invite", ['user_id' => $this->participantUser->id]);
        $response->assertStatus(403);
    }

    // Public Calendar Test
    #[Test]
    public function public_calendar_returns_safe_data()
    {
        Meeting::factory()->create();
        $response = $this->getJson('/api/public/calendar?start_date='.now()->subDay()->toDateString().'&end_date='.now()->addMonths(2)->toDateString());
        $response->assertOk()
            ->assertJsonStructure(['data' => [['id', 'topic', 'start_time']]])
            ->assertJsonMissingPath('data.0.zoom_meeting')
            ->assertJsonMissingPath('data.0.organizer');
    }

    // Create Meeting with Participants Test
    #[Test]
    public function can_create_meeting_with_participants()
    {
        $data = [
            'topic' => 'Meeting With Participants',
            'start_time' => now()->addDays(5)->toDateTimeString(),
            'duration' => 60,
            'type' => 'offline',
            'location_id' => \App\Models\MeetingLocation::factory()->create()->id,
            'participants' => [$this->participantUser->id, $this->unrelatedUser->id],
        ];

        $response = $this->actingAs($this->organizerUser)->postJson('/api/meetings', $data);
        $response->assertStatus(201);
        $this->assertDatabaseHas('meetings', ['topic' => 'Meeting With Participants']);
        $this->assertDatabaseCount('meeting_user', 2);
    }
}
