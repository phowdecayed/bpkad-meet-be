<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MeetingResource;
use App\Models\Meeting;
use App\Services\MeetingService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MeetingController extends Controller
{
    protected $meetingService;

    public function __construct(MeetingService $meetingService)
    {
        $this->meetingService = $meetingService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return MeetingResource::collection(Meeting::with(['location', 'zoomMeeting'])->latest()->paginate());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $this->validateStoreRequest($request->all());

        $meeting = $this->meetingService->createMeeting($validated);

        return (new MeetingResource($meeting->load(['location', 'zoomMeeting'])))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Validate a store request.
     *
     * @param array $data
     * @return array
     */
    public function validateStoreRequest(array $data): array
    {
        return validator($data, [
            'topic' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_time' => 'required|date',
            'duration' => 'required|integer|min:1',
            'type' => ['required', Rule::in(['online', 'offline', 'hybrid'])],
            'location_id' => [
                'nullable',
                Rule::requiredIf(fn () => in_array($data['type'] ?? null, ['offline', 'hybrid'])),
                'exists:meeting_locations,id',
            ],
            'password' => 'nullable|string|max:10', // Zoom password validation
            'settings' => 'nullable|array', // For Zoom settings
        ])->validate();
    }

    /**
     * Display the specified resource.
     */
    public function show(Meeting $meeting)
    {
        return new MeetingResource($meeting->load(['location', 'zoomMeeting']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Meeting $meeting)
    {
        $this->meetingService->deleteMeeting($meeting);

        return response()->json(['message' => 'Meeting deleted successfully.'], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Meeting $meeting)
    {
        $validated = $request->validate([
            'topic' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'start_time' => 'sometimes|required|date',
            'duration' => 'sometimes|required|integer|min:1',
            'location_id' => 'nullable|exists:meeting_locations,id',
            'settings' => 'nullable|array',
        ]);

        $updatedMeeting = $this->meetingService->updateMeeting($meeting, $validated);

        return new MeetingResource($updatedMeeting->load(['location', 'zoomMeeting']));
    }
}
