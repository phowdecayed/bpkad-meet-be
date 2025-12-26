<?php

namespace Tests\Feature;

use App\Models\Meeting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SoftDeleteUserTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function meeting_persists_when_organizer_is_soft_deleted()
    {
        $user = User::factory()->create();
        $meeting = Meeting::factory()->create(['organizer_id' => $user->id]);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'deleted_at' => null]);

        $user->delete();

        $this->assertSoftDeleted($user);
        $this->assertDatabaseHas('meetings', ['id' => $meeting->id]);

        // Refresh meeting to check relationship
        $meeting->refresh();
        $this->assertNotNull($meeting->organizer);
        $this->assertEquals($user->id, $meeting->organizer->id);
    }
}
