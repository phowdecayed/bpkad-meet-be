<?php

namespace App\Http\Controllers\Api;

use App\Enums\MeetingType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMeetingRequest;
use App\Http\Requests\Zoom\DeleteMeetingRequest;
use App\Http\Requests\Zoom\GetMeetingRequest;
use App\Models\ZoomMeeting;
use App\Services\MeetingService;
use App\Services\ZoomService;
use Illuminate\Http\JsonResponse;
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
    public function authenticate(): JsonResponse
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
    public function createMeeting(StoreMeetingRequest $request): JsonResponse
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
    public function deleteMeeting(DeleteMeetingRequest $request): JsonResponse
    {
        $meetingId = $request->validated()['meetingId'];

        $zoomMeeting = ZoomMeeting::where('zoom_id', $meetingId)->first();

        if ($zoomMeeting && $zoomMeeting->meeting) {
            $this->authorize('delete', $zoomMeeting->meeting);
        } else {
            // If the meeting is not in our database, only allow admins to delete it directly from Zoom
            // or deny it completely. For safety, we deny it unless the user serves a special role,
            // but simply returning 404/403 is safer for now.
            if (! $request->user()->can('manage meetings')) {
                abort(404, 'Meeting not found in local records.');
            }
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
    public function getMeeting(GetMeetingRequest $request): JsonResponse
    {
        $meetingId = $request->validated()['meetingId'] ?? null;

        if (! $meetingId) {
            // List all meetings - only allow if admin, as this leaks all account meetings
            if (! $request->user()->can('manage meetings')) {
                abort(403, 'Only admins can list all specific Zoom meetings directly.');
            }
        } else {
            // Get specific meeting
            $zoomMeeting = ZoomMeeting::where('zoom_id', $meetingId)->first();
            if ($zoomMeeting && $zoomMeeting->meeting) {
                $this->authorize('view', $zoomMeeting->meeting);
            }
        }

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
    public function getMeetingSummary(string $meetingUuid): JsonResponse
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
    public function getPastMeetingDetails(GetMeetingRequest $request): JsonResponse
    {
        $meetingId = $request->validated()['meetingId'] ?? null;

        if ($meetingId) {
            $zoomMeeting = ZoomMeeting::where('zoom_id', $meetingId)->first();
            if ($zoomMeeting && $zoomMeeting->meeting) {
                $this->authorize('view', $zoomMeeting->meeting);
            }
        }

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
     * Sync data from Zoom (Recordings & Summary).
     */
    public function syncZoomData(Request $request, string $meetingId): JsonResponse
    {
        $this->authorize('manage meetings'); // Using general permission

        $zoomMeeting = ZoomMeeting::where('zoom_id', $meetingId)->firstOrFail();
        $setting = $zoomMeeting->setting;

        if (! $setting) {
            return response()->json(['message' => 'Zoom setting not found.'], 404);
        }

        $credentials = $setting->payload;
        $this->zoomService->setCredentials(
            $credentials['client_id'],
            $credentials['client_secret'],
            $credentials['account_id']
        );

        // 1. Fetch Recordings
        $recordingResponse = $this->zoomService->getRecordings($meetingId);
        if ($recordingResponse->successful()) {
            $data = $recordingResponse->json();
            // Zoom returns array of recording files. We construct a play url.
            // Simplified: grab share_url from top level or first file?
            // Usually 'share_url' is at root of response for the set.
            $zoomMeeting->recording_play_url = $data['share_url'] ?? null;
            $zoomMeeting->recording_passcode = $data['password'] ?? null; // Recording password if any
        }

        // 2. Fetch Summary
        // Note: meeting_summary endpoint uses UUID usually, but Zoom documentation says meetingId also works?
        // Actually earlier search said /meetings/{meetingId}/meeting_summary.
        // We try with zoom_id first.
        $summaryResponse = $this->zoomService->getMeetingSummary($meetingId);

        // Handle 404 if summary not ready
        if ($summaryResponse->successful()) {
            $summaryData = $summaryResponse->json();
            // Format: summary_title, summary_details (text)
            // We just store the whole content or specific text?
            // Let's store summary_details as text.
            $zoomMeeting->summary_content = $summaryData['summary_details'] ?? serialize($summaryData); // Fallback storage
        }

        $zoomMeeting->save();

        return response()->json([
            'message' => 'Zoom data synced successfully.',
            'data' => $zoomMeeting->refresh(),
        ]);
    }

    /**
     * Update a specific Zoom meeting.
     */
    public function updateMeeting(Request $request): JsonResponse
    {
        $validated = $request->validated();
        $meetingId = $validated['meetingId'];

        $zoomMeeting = ZoomMeeting::where('zoom_id', $meetingId)->first();

        if ($zoomMeeting && $zoomMeeting->meeting) {
            $this->authorize('update', $zoomMeeting->meeting);
        } else {
            if (! $request->user()->can('manage meetings')) {
                abort(404, 'Meeting not found in local records.');
            }
        }

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
