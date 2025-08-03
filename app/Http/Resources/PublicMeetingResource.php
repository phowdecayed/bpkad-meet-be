<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicMeetingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'topic' => $this->topic,
            'description' => $this->description,
            'start_time' => $this->start_time,
            'duration' => $this->duration,
            'type' => $this->type,
            'location' => $this->whenLoaded('location', function () {
                return [
                    'name' => $this->location->name,
                    'address' => $this->location->address,
                    'room_name' => $this->location->room_name,
                ];
            }),
        ];
    }
}
