<?php

namespace App\Services;

use App\Models\Meeting;
use App\Models\Setting;
use App\Models\ZoomMeeting;
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
     * @param array $data
     * @return Meeting
     * @throws \Exception
     */
    public function createMeeting(array $data): Meeting
    {
        // Use a database transaction to ensure data integrity
        return DB::transaction(function () use ($data) {
            // 1. Create the core meeting record
            $meeting = Meeting::create([
                'organizer_id' => $data['organizer_id'],
                'topic' => $data['topic'],
                'description' => $data['description'] ?? null,
                'start_time' => $data['start_time'],
                'duration' => $data['duration'],
                'type' => $data['type'],
                'location_id' => $data['location_id'] ?? null,
            ]);

            // 3. If participants are included, attach them to the meeting
            if (!empty($data['participants'])) {
                $meeting->participants()->sync($data['participants']);
            }

            // 4. If the meeting is online or hybrid, create a Zoom meeting
            if (in_array($data['type'], ['online', 'hybrid'])) {
                // Find all available zoom credentials from settings
                $zoomSettings = Setting::where('group', 'zoom')->get();

                if ($zoomSettings->isEmpty()) {
                    throw ValidationException::withMessages([
                        'zoom_api' => 'Zoom integration settings are not configured.'
                    ]);
                }

                $selectedSetting = null;
                foreach ($zoomSettings as $setting) {
                    $activeMeetingsCount = ZoomMeeting::where('setting_id', $setting->id)
                        ->where('start_time', '<=', now())
                        ->get()
                        ->filter(function ($zoomMeeting) {
                            return $zoomMeeting->start_time->addMinutes($zoomMeeting->duration)->isFuture();
                        })->count();

                    if ($activeMeetingsCount < 2) {
                        $selectedSetting = $setting;
                        break;
                    }
                }

                if (!$selectedSetting) {
                    throw ValidationException::withMessages([
                        'zoom_api' => 'All Zoom accounts are currently busy. Please try again later.'
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
                        'start_time' => $data['start_time'],
                        'duration' => $data['duration'],
                        'password' => $data['password'] ?? null,
                        // Pass any other zoom-specific settings from the request
                        'settings' => $data['settings'] ?? [],
                    ],
                    $meeting->id, // Pass the parent meeting ID
                    $selectedSetting->id
                );

                if (!$zoomResponse->successful()) {
                    // If Zoom API call fails, roll back the transaction
                    throw ValidationException::withMessages([
                        'zoom_api' => 'Failed to create Zoom meeting: ' . $zoomResponse->body()
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
     *
     * @param Meeting $meeting
     * @return void
     */
    public function deleteMeeting(Meeting $meeting): void
    {
        // If the meeting has an associated Zoom meeting, delete it from Zoom first.
        if ($meeting->zoomMeeting) {
            $zoomSetting = $meeting->zoomMeeting->setting;

            if (!$zoomSetting) {
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
     *
     * @param Meeting $meeting
     * @param array $data
     * @return Meeting
     */
    public function updateMeeting(Meeting $meeting, array $data): Meeting
    {
        return DB::transaction(function () use ($meeting, $data) {
            $meeting->update($data);

            // If the meeting is online or hybrid and has a zoom meeting, update it.
            if ($meeting->zoomMeeting && in_array($meeting->type, ['online', 'hybrid'])) {
                $zoomSetting = $meeting->zoomMeeting->setting;

                if (!$zoomSetting) {
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
}
