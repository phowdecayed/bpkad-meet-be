<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMeetingRequest;
use App\Services\MeetingService;
use App\Services\ZoomService;
use Illuminate\Http\Request;

class ZoomController extends Controller
{
    protected $zoomService;

    protected $meetingService;

    public function __construct(ZoomService $zoomService, MeetingService $meetingService)
    {
        $this->zoomService = $zoomService;
        $this->meetingService = $meetingService;
    }

    /**
     * Authenticate with Zoom and cache the token.
     */
    public function authenticate()
    {
        try {
            return $this->zoomService->authenticate();
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * [LEGACY] Create a new Zoom meeting.
     * This is an alias for creating a meeting of type 'online' via the core meeting endpoint.
     */
    public function createMeeting(Request $request)
    {
        $data = $request->all();
        $data['type'] = 'online'; // Force the type to online for this legacy route

        // Use validate from StoreMeetingRequest to ensure consistency
        $validated = validator($data, (new StoreMeetingRequest)->rules())->validate();

        $meeting = $this->meetingService->createMeeting($validated);

        return response()->json($meeting, 201);
    }

    /**
     * Delete a Zoom meeting.
     */
    public function deleteMeeting(Request $request)
    {
        $meetingId = $request->input('meetingId');

        if (! $meetingId) {
            return response()->json(['error' => 'meetingId parameter is required.'], 400);
        }

        try {
            $response = $this->zoomService->deleteMeeting($meetingId);
            // Zoom API returns 204 No Content on successful deletion
            if ($response->successful()) {
                return response()->json(['message' => 'Meeting deleted successfully.'], 200);
            }

            return response()->json($response->json(), $response->status());
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get a specific Zoom meeting or list all meetings.
     */
    public function getMeeting(Request $request)
    {
        $meetingId = $request->input('meetingId');

        try {
            if ($meetingId) {
                // If meetingId is provided, get that specific meeting.
                $response = $this->zoomService->getMeeting($meetingId);
            } else {
                // Otherwise, list all meetings for the user.
                $response = $this->zoomService->listMeetings();
            }

            return response()->json($response->json(), $response->status());
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get the summary for a specific Zoom meeting.
     */
    public function getMeetingSummary(string $meetingUuid)
    {
        try {
            $response = $this->zoomService->getMeetingSummary($meetingUuid);

            return response()->json($response->json(), $response->status());
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get details for a past Zoom meeting.
     */
    public function getPastMeetingDetails(Request $request)
    {
        $meetingId = $request->input('meetingId');

        if (! $meetingId) {
            return response()->json(['error' => 'meetingId parameter is required.'], 400);
        }

        try {
            $response = $this->zoomService->getPastMeetingDetails($meetingId);

            return response()->json($response->json(), $response->status());
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update a specific Zoom meeting.
     */
    public function updateMeeting(Request $request)
    {
        $meetingId = $request->input('meetingId');
        $data = $request->except('meetingId');

        if (! $meetingId) {
            return response()->json(['error' => 'meetingId parameter is required.'], 400);
        }

        try {
            $response = $this->zoomService->updateMeeting($meetingId, $data);
            // Zoom API returns 204 No Content on successful update
            if ($response->successful()) {
                return response()->json(['message' => 'Meeting updated successfully.'], 200);
            }

            return response()->json($response->json(), $response->status());
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
