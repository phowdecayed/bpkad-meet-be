<?php

namespace Tests\Feature;

use App\Enums\MeetingType;
use App\Models\Meeting;
use App\Models\Setting;
use App\Models\User;
use App\Models\ZoomMeeting;
use App\Services\ZoomService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Mockery\MockInterface;

class LoadBalancingTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_distributes_meetings_across_multiple_accounts()
    {
        // 1. Setup: Create 2 Zoom Accounts
        $account1 = Setting::factory()->create(['name' => 'zoom1', 'group' => 'zoom', 'payload' => ['account_id' => 'acc1', 'client_id' => 'c1', 'client_secret' => 's1']]);
        $account2 = Setting::factory()->create(['name' => 'zoom2', 'group' => 'zoom', 'payload' => ['account_id' => 'acc2', 'client_id' => 'c2', 'client_secret' => 's2']]);

        \Spatie\Permission\Models\Permission::create(['name' => 'create meetings']);
        $user = User::factory()->create();
        $user->givePermissionTo('create meetings');

        // Mock ZoomService to succeed
        $this->mock(ZoomService::class, function (MockInterface $mock) {
            $mock->shouldReceive('setCredentials')->times(3); // Expect 3 calls (2 on acc1, 1 on acc2)
            $mock->shouldReceive('createMeeting')->andReturn(new \Illuminate\Http\Client\Response(
                new \GuzzleHttp\Psr7\Response(201, [], json_encode(['id' => 123, 'join_url' => 'http://zoom...']))
            ));
        });

        // 2. Create 2 meetings covering the same timeslot (Should fill Account 1)
        // Meeting 1
        $this->actingAs($user)->postJson('/api/meetings', [
            'topic' => 'Meeting 1', 'start_time' => now()->addDay(), 'duration' => 60, 'type' => MeetingType::ONLINE->value
        ])->assertCreated();

        // Meeting 2
        $this->actingAs($user)->postJson('/api/meetings', [
             'topic' => 'Meeting 2', 'start_time' => now()->addDay(), 'duration' => 60, 'type' => MeetingType::ONLINE->value
        ])->assertCreated();

        // 3. Create 3rd meeting. Account 1 is full (2/2). Should go to Account 2.
        $this->actingAs($user)->postJson('/api/meetings', [
             'topic' => 'Meeting 3', 'start_time' => now()->addDay(), 'duration' => 60, 'type' => MeetingType::ONLINE->value
        ])->assertCreated();

        // 4. Verify Assignments
        // First 2 should be on Account 1
        $this->assertEquals(2, ZoomMeeting::where('setting_id', $account1->id)->count());
        // 3rd should be on Account 2
        $this->assertEquals(1, ZoomMeeting::where('setting_id', $account2->id)->count());
    }
}
