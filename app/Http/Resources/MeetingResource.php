<?php

namespace App\Http\Resources;

use App\Http\Resources\UserResource;
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
        $user = $request->user();

        return [
            'id' => $this->id,
            'organizer' => new UserResource($this->whenLoaded('organizer')),
            'topic' => $this->topic,
            'description' => $this->description,
            'start_time' => $this->start_time,
            'duration' => $this->duration,
            'type' => $this->type,
            'host_key' => $this->when(
                isset($this->host_key) && $user && $user->can('viewHostKey', $this->resource),
                $this->host_key
            ),
            'location' => new MeetingLocationResource($this->whenLoaded('location')),
            'zoom_meeting' => $this->whenLoaded('zoomMeeting'),
        ];
    }
}