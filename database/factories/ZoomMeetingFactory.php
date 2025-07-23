<?php

namespace Database\Factories;

use App\Models\Meeting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ZoomMeeting>
 */
class ZoomMeetingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'zoom_id' => $this->faker->unique()->randomNumber(8),
            'uuid' => $this->faker->uuid,
            'host_id' => 'host_' . $this->faker->uuid,
            'host_email' => $this->faker->safeEmail,
            'type' => 2,
            'status' => 'waiting',
            'start_time' => $this->faker->dateTimeBetween('+1 day', '+1 month'),
            'duration' => 60,
            'timezone' => 'Asia/Jakarta',
            'created_at_zoom' => now(),
            'start_url' => $this->faker->url,
            'join_url' => $this->faker->url,
            'password' => $this->faker->password(8),
            'settings' => ['host_video' => true],
        ];
    }
}