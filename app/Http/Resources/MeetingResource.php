<?php

namespace App\Http\Resources;

use App\Enums\MeetingType;
use App\Models\Setting;
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
        $hostKey = null;

        if (isset($this->host_key)) {
            // This handles the case where the key is attached during creation
            $hostKey = $this->host_key;
        } elseif (in_array($this->type->value, [MeetingType::ONLINE->value, MeetingType::HYBRID->value])) {
            // This handles fetching the key for existing models
            $zoomSetting = Setting::where('group', 'zoom')->first();
            if ($zoomSetting && isset($zoomSetting->payload['host_key'])) {
                $hostKey = $zoomSetting->payload['host_key'];
            }
        }

        return [
            'id' => $this->id,
            'organizer' => new UserResource($this->whenLoaded('organizer')),
            'topic' => $this->topic,
            'description' => $this->description,
            'start_time' => $this->start_time,
            'duration' => $this->duration,
            'type' => $this->type,
            'host_key' => $this->when(
                $hostKey && $user && $user->can('viewHostKey', $this->resource),
                $hostKey
            ),
            'location' => new MeetingLocationResource($this->whenLoaded('location')),
            'zoom_meeting' => $this->whenLoaded('zoomMeeting'),
            'participants' => UserResource::collection($this->whenLoaded('participants')),
        ];
    }
}
