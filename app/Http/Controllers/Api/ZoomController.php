<?php

namespace App\Http\Controllers\Api;

use App\Enums\MeetingType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMeetingRequest;
use App\Http\Requests\Zoom\DeleteMeetingRequest;
use App\Http\Requests\Zoom\GetMeetingRequest;
use App\Http\Requests\Zoom\UpdateMeetingRequest;
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
    public function createMeeting(StoreMeetingRequest $request)
    {
        // StoreMeetingRequest already handles validation and strict typing
        $validated = $request->validated();

        // Ensure type matches legacy expectation if needed, or rely on request rule
        if (! isset($validated['type'])) {
            $validated['type'] = MeetingType::ONLINE->value;
        }

        $meeting = $this->meetingService->createMeeting($validated);

        return response()->json($meeting, 201);
    }

    /**
     * Delete a Zoom meeting.
     */
    public function deleteMeeting(DeleteMeetingRequest $request)
    {
        $meetingId = $request->validated()['meetingId'];

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
    public function getMeeting(GetMeetingRequest $request)
    {
        $meetingId = $request->validated()['meetingId'] ?? null;

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
    public function getPastMeetingDetails(GetMeetingRequest $request)
    {
        $meetingId = $request->validated()['meetingId'] ?? null;

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
    public function updateMeeting(UpdateMeetingRequest $request)
    {
        $validated = $request->validated();
        $meetingId = $validated['meetingId'];
        // Remove meetingId from data sent to Zoom
        unset($validated['meetingId']);
        $data = $validated;

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
