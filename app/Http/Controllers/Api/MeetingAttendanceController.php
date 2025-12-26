<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Meeting;
use App\Models\MeetingAttendance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeetingAttendanceController extends Controller
{
    /**
     * Store attendance for a meeting (Public access via UUID).
     */
    public function storePublic(Request $request, string $uuid): JsonResponse
    {
        $meeting = Meeting::where('uuid', $uuid)->firstOrFail();

        // Basic validation
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'agency' => 'nullable|string|max:255',
            'signature_path' => 'nullable|string', // Assuming base64 or path handled by FE upload first? Keeping simple for now.
        ]);

        // Check for double check-in (optional, using name/email/device logic?)
        // For simplicity, we allow based on unique constraint if we added one, otherwise logic check:
        // logic: if email is provided, check if email already checked in.
        if (! empty($validated['email'])) {
            $exists = MeetingAttendance::where('meeting_id', $meeting->id)
                ->where('email', $validated['email'])
                ->exists();
            if ($exists) {
                // Return success anyway to prevent leakage/spam, or error? Let's return error for now.
                return response()->json(['message' => 'Attendance already recorded.'], 422);
            }
        }

        $attendance = $meeting->attendances()->create([
            'user_id' => $request->user('sanctum')?->id, // If they happen to be logged in
            'name' => $validated['name'],
            'email' => $validated['email'],
            'agency' => $validated['agency'],
            'signature_path' => $validated['signature_path'],
        ]);

        return response()->json([
            'message' => 'Attendance recorded successfully.',
            'data' => $attendance,
        ], 201);
    }

    /**
     * List attendances for a specific meeting (Authenticated).
     */
    public function index(Request $request, Meeting $meeting): JsonResponse
    {
        // Authorization check: User must be able to view the meeting (organizer or participant)
        // Re-using existing policy logic or manual check
        if ($request->user()->cannot('view', $meeting)) {
            abort(403, 'Unauthorized access to meeting attendance.');
        }

        $attendances = $meeting->attendances()->latest()->get();

        return response()->json([
            'data' => $attendances,
        ]);
    }
}
