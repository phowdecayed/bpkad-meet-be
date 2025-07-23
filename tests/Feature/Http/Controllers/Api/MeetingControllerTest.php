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
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MeetingControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        Sanctum::actingAs(User::factory()->create(), ['*']);
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
        $startTime = now()->addDays(5)->startOfSecond();
        $data = [
            'topic' => 'Online Meeting Test',
            'start_time' => $startTime->toDateTimeString(),
            'duration' => 60,
            'type' => 'online',
        ];

        $response = $this->postJson('/api/meetings', $data);

        $response->assertStatus(201);
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
        $this->mock(ZoomService::class, function ($mock) {
            $mock->shouldReceive('deleteMeeting')
                ->once()
                ->andReturn(new Response(Http::response(null, 204)));
        });

        $meeting = Meeting::factory()->create(['type' => 'online']);
        ZoomMeeting::factory()->create(['meeting_id' => $meeting->id]);

        $response = $this->deleteJson("/api/meetings/{$meeting->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('meetings', ['id' => $meeting->id]);
    }
}
