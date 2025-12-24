<?php

namespace App\Services;

use App\Enums\MeetingType;
use App\Models\Meeting;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MeetingService
{
    protected $zoomService;

    public function __construct(ZoomService $zoomService)
    {
        $this->zoomService = $zoomService;
    }

    /**
     * Create a new meeting (online, offline, or hybrid).
     *
     * @throws \Exception
     */
    public function createMeeting(array $data): Meeting
    {
        // Parse the start time using the application's timezone.
        $startTime = Carbon::parse($data['start_time']);

        // 1. Check for location conflicts for offline or hybrid meetings
        if (in_array($data['type'], [MeetingType::OFFLINE->value, MeetingType::HYBRID->value]) && isset($data['location_id'])) {
            $this->checkForLocationConflict(
                $data['location_id'],
                $startTime,
                $data['duration']
            );
        }

        // Use a database transaction to ensure data integrity
        return DB::transaction(function () use ($data, $startTime) {
            // 2. Create the core meeting record
            $meeting = Meeting::create([
                'organizer_id' => $data['organizer_id'],
                'topic' => $data['topic'],
                'description' => $data['description'] ?? null,
                'start_time' => $startTime, // Use the parsed Carbon object
                'duration' => $data['duration'],
                'type' => $data['type'],
                'location_id' => $data['location_id'] ?? null,
            ]);

            // 3. If participants are included, attach them to the meeting
            if (! empty($data['participants'])) {
                $meeting->participants()->sync($data['participants']);
            }

            // 4. If the meeting is online or hybrid, create a Zoom meeting
            if (in_array($data['type'], [MeetingType::ONLINE->value, MeetingType::HYBRID->value])) {
                // Find all available zoom credentials from settings
                $zoomSettings = Setting::where('group', 'zoom')->get();

                if ($zoomSettings->isEmpty()) {
                    throw ValidationException::withMessages([
                        'zoom_api' => 'Zoom integration settings are not configured.',
                    ]);
                }

                $endTime = $startTime->copy()->addMinutes($data['duration']);

                $selectedSetting = null;
                foreach ($zoomSettings as $setting) {
                    // Get all meetings for this setting and check conflicts in PHP
                    $existingMeetings = Meeting::whereHas('zoomMeeting', function ($query) use ($setting) {
                        $query->where('setting_id', $setting->id);
                    })
                        ->where('start_time', '<', $endTime)
                        ->get(['id', 'start_time', 'duration']);

                    $conflictingMeetingsCount = 0;
                    foreach ($existingMeetings as $existingMeeting) {
                        $existingEndTime = Carbon::parse($existingMeeting->start_time)->addMinutes($existingMeeting->duration);
                        if ($existingEndTime > $startTime) {
                            $conflictingMeetingsCount++;
                        }
                    }

                    if ($conflictingMeetingsCount < 2) {
                        $selectedSetting = $setting;
                        break;
                    }
                }

                if (! $selectedSetting) {
                    throw ValidationException::withMessages([
                        'zoom_api' => 'All available Zoom accounts are at their maximum concurrent meeting limit for the selected time.',
                    ]);
                }

                $credentials = $selectedSetting->payload;
                $this->zoomService->setCredentials(
                    $credentials['client_id'],
                    $credentials['client_secret'],
                    $credentials['account_id']
                );

                $zoomResponse = $this->zoomService->createMeeting(
                    [
                        'topic' => $data['topic'],
                        // Send the time to Zoom in UTC format, as required by their API
                        'start_time' => $startTime->clone()->utc()->toIso8601String(),
                        'duration' => $data['duration'],
                        'password' => $data['password'] ?? null,
                        // Pass any other zoom-specific settings from the request
                        'settings' => $data['settings'] ?? [],
                    ],
                    $meeting->id, // Pass the parent meeting ID
                    $selectedSetting->id
                );

                if (! $zoomResponse->successful()) {
                    // If Zoom API call fails, roll back the transaction
                    throw ValidationException::withMessages([
                        'zoom_api' => 'Failed to create Zoom meeting: '.$zoomResponse->body(),
                    ]);
                }

                // Attach the host_key to the meeting model for the response
                if (isset($credentials['host_key'])) {
                    $meeting->host_key = $credentials['host_key'];
                }
            }

            // Eager load the relationships for the response
            return $meeting->load(['location', 'zoomMeeting']);
        });
    }

    /**
     * Delete a meeting.
     */
    public function deleteMeeting(Meeting $meeting): void
    {
        // If the meeting has an associated Zoom meeting, delete it from Zoom first.
        if ($meeting->zoomMeeting) {
            $zoomSetting = $meeting->zoomMeeting->setting;

            if (! $zoomSetting) {
                // Fallback to the first available setting if the associated one is not found
                $zoomSetting = Setting::where('group', 'zoom')->first();
            }

            if ($zoomSetting) {
                $credentials = $zoomSetting->payload;
                $this->zoomService->setCredentials(
                    $credentials['client_id'],
                    $credentials['client_secret'],
                    $credentials['account_id']
                );
                $this->zoomService->deleteMeeting($meeting->zoomMeeting->zoom_id);
            }
        }

        // Delete the core meeting record.
        // The related zoom_meetings record will be deleted automatically by the
        // database cascade rule.
        $meeting->delete();
    }

    /**
     * Update a meeting.
     */
    public function updateMeeting(Meeting $meeting, array $data): Meeting
    {
        // Determine the parameters for the conflict check
        $locationId = $data['location_id'] ?? $meeting->location_id;
        $startTime = $data['start_time'] ?? $meeting->start_time;
        $duration = $data['duration'] ?? $meeting->duration;
        $type = $data['type'] ?? $meeting->type;

        // Check for location conflicts if relevant fields are being updated
        // Note: type from data is string, type from model is Enum
        $typeValue = $type instanceof MeetingType ? $type->value : $type;

        if (in_array($typeValue, [MeetingType::OFFLINE->value, MeetingType::HYBRID->value]) && $locationId) {
            $this->checkForLocationConflict(
                $locationId,
                $startTime,
                $duration,
                $meeting->id // Exclude the current meeting from the check
            );
        }

        return DB::transaction(function () use ($meeting, $data) {
            $meeting->update($data);

            // If the meeting is online or hybrid and has a zoom meeting, update it.
            if ($meeting->zoomMeeting && in_array($meeting->type, [MeetingType::ONLINE, MeetingType::HYBRID])) {
                $zoomSetting = $meeting->zoomMeeting->setting;

                if (! $zoomSetting) {
                    // Fallback to the first available setting if the associated one is not found
                    $zoomSetting = Setting::where('group', 'zoom')->first();
                }

                if ($zoomSetting) {
                    $credentials = $zoomSetting->payload;
                    $this->zoomService->setCredentials(
                        $credentials['client_id'],
                        $credentials['client_secret'],
                        $credentials['account_id']
                    );
                    $this->zoomService->updateMeeting($meeting->zoomMeeting->zoom_id, $data);
                }
            }

            return $meeting->load(['organizer', 'location', 'zoomMeeting']);
        });
    }

    /**
     * Check for scheduling conflicts for a given location and time.
     *
     * @throws ValidationException
     */
    private function checkForLocationConflict(int $locationId, string $startTime, int $duration, ?int $excludeMeetingId = null): void
    {
        $newStartTime = Carbon::parse($startTime);
        $newEndTime = $newStartTime->copy()->addMinutes($duration);

        $query = Meeting::where('location_id', $locationId)
            ->whereIn('type', [MeetingType::OFFLINE, MeetingType::HYBRID]);

        if ($excludeMeetingId) {
            $query->where('id', '!=', $excludeMeetingId);
        }

        $meetings = $query->get();

        foreach ($meetings as $meeting) {
            $existingStartTime = Carbon::parse($meeting->start_time);
            $existingEndTime = $existingStartTime->copy()->addMinutes($meeting->duration);

            // Check for overlap
            if ($newStartTime < $existingEndTime && $newEndTime > $existingStartTime) {
                throw ValidationException::withMessages([
                    'location_id' => 'This location is already booked for the selected time slot.',
                ]);
            }
        }
    }
}
