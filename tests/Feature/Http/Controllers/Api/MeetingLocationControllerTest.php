<?php

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\MeetingLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MeetingLocationControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        // Authenticate a user for all tests in this class
        Sanctum::actingAs(User::factory()->create(), ['*']);
    }

    /** @test */
    public function it_can_list_meeting_locations()
    {
        MeetingLocation::factory()->count(3)->create();

        $response = $this->getJson('/api/meeting-locations');

        $response->assertOk()
            ->assertJsonCount(3);
    }

    /** @test */
    public function it_can_create_a_meeting_location()
    {
        $data = [
            'name' => $this->faker->company,
            'address' => $this->faker->address,
            'room_name' => 'Conference Room ' . $this->faker->randomLetter,
            'capacity' => $this->faker->numberBetween(10, 100),
        ];

        $response = $this->postJson('/api/meeting-locations', $data);

        $response->assertStatus(201)
            ->assertJsonFragment($data);

        $this->assertDatabaseHas('meeting_locations', $data);
    }

    /** @test */
    public function it_can_show_a_meeting_location()
    {
        $location = MeetingLocation::factory()->create();

        $response = $this->getJson("/api/meeting-locations/{$location->id}");

        $response->assertOk()
            ->assertJsonFragment(['id' => $location->id]);
    }

    /** @test */
    public function it_can_update_a_meeting_location()
    {
        $location = MeetingLocation::factory()->create();
        $updateData = ['name' => 'Updated Location Name'];

        $response = $this->patchJson("/api/meeting-locations/{$location->id}", $updateData);

        $response->assertOk()
            ->assertJsonFragment($updateData);

        $this->assertDatabaseHas('meeting_locations', $updateData);
    }

    /** @test */
    public function it_can_delete_a_meeting_location()
    {
        $location = MeetingLocation::factory()->create();

        $response = $this->deleteJson("/api/meeting-locations/{$location->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('meeting_locations', ['id' => $location->id]);
    }
}