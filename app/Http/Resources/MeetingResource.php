<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MeetingResource extends JsonResource
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
            'host_key' => $this->when(isset($this->host_key), $this->host_key),
            'location' => new MeetingLocationResource($this->whenLoaded('location')),
            'zoom_meeting' => $this->whenLoaded('zoomMeeting'),
        ];
    }
}