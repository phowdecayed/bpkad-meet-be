<?php

namespace Database\Factories;

use App\Models\MeetingLocation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Meeting>
 */
class MeetingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organizer_id' => User::factory(),
            'topic' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph,
            'start_time' => $this->faker->dateTimeBetween('+1 day', '+1 month'),
            'duration' => $this->faker->randomElement([30, 60, 90]),
            'type' => 'offline', // Default to offline to avoid Zoom API calls
            'location_id' => MeetingLocation::factory(),
        ];
    }
}