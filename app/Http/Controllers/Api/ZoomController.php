<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ZoomService;
use Illuminate\Http\Request;

class ZoomController extends Controller
{
    protected $zoomService;

    public function __construct(ZoomService $zoomService)
    {
        $this->zoomService = $zoomService;
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
     * Create a new Zoom meeting.
     */
    public function createMeeting(Request $request)
    {
        try {
            $response = $this->zoomService->createMeeting($request->all());
            return response()->json($response->json(), $response->status());
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete a Zoom meeting.
     */
    public function deleteMeeting(Request $request)
    {
        $meetingId = $request->input('meetingId');

        if (!$meetingId) {
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
}