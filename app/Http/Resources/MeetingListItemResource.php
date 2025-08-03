<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MeetingListItemResource extends JsonResource
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
            'organizer' => new UserResource($this->whenLoaded('organizer')),
            'topic' => $this->topic,
            'start_time' => $this->start_time,
            'duration' => $this->duration,
            'type' => $this->type,
            'location' => new MeetingLocationResource($this->whenLoaded('location')),
        ];
    }
}
