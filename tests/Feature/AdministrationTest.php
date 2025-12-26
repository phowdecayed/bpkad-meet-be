<?php

namespace Tests\Feature;

use App\Models\Meeting;
use App\Models\MeetingAttendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AdministrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_organizer_can_update_meeting_with_notulen()
    {
        // Setup
        Permission::create(['name' => 'edit meetings']);
        $user = User::factory()->create();
        $user->givePermissionTo('edit meetings');

        $meeting = Meeting::factory()->create(['organizer_id' => $user->id]);

        $this->actingAs($user);

        $response = $this->patchJson("/api/meetings/{$meeting->id}", [
            'notulen' => 'Hasil rapat: Keputusan final disetujui.',
            // Pass required fields for update validation if strictly required or confirm 'sometimes' works
            'topic' => $meeting->topic,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('meetings', [
            'id' => $meeting->id,
            'notulen' => 'Hasil rapat: Keputusan final disetujui.',
        ]);
    }

    public function test_organizer_can_export_attendance_as_csv()
    {
        // Setup
        Permission::create(['name' => 'view meetings']);
        $user = User::factory()->create();
        $user->givePermissionTo('view meetings');

        $meeting = Meeting::factory()->create(['organizer_id' => $user->id]);
        MeetingAttendance::create([
            'meeting_id' => $meeting->id,
            'name' => 'Peserta 1',
            'email' => 'p1@test.com',
            'agency' => 'Agency A',
        ]);
        MeetingAttendance::create([
            'meeting_id' => $meeting->id,
            'name' => 'Peserta 2',
            'email' => 'p2@test.com',
            'agency' => 'Agency B',
        ]);

        $this->actingAs($user);

        $response = $this->get("/api/meetings/{$meeting->id}/attendances/export");

        $response->assertOk();

        // Asset Headers
        $response->assertHeader('Content-Type', 'text/csv; charset=utf-8');
        $response->assertHeader('Content-Disposition', 'attachment; filename="attendance-'.$meeting->uuid.'.csv"');

        // Assert Content (Streamed content is sometimes tricky to inspect, but Laravel test response captures it)
        $content = $response->streamedContent();

        $this->assertStringContainsString('Name', $content);
        $this->assertStringContainsString('Email', $content);
        $this->assertStringContainsString('Peserta 1', $content);
        $this->assertStringContainsString('p1@test.com', $content);
    }
}
