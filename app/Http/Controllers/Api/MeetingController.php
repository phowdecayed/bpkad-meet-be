<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MeetingListItemResource;
use App\Http\Resources\MeetingResource;
use App\Http\Resources\PublicMeetingResource;
use App\Http\Resources\UserResource;
use App\Models\Meeting;
use App\Models\User;
use App\Rules\NoTimeConflict;
use App\Services\MeetingService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MeetingController extends Controller
{
    use AuthorizesRequests;

    protected $meetingService;

    public function __construct(MeetingService $meetingService)
    {
        $this->meetingService = $meetingService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Meeting::query()->with(['organizer', 'location']);

        // Check the policy to determine which meetings to show
        if ($user->can('viewAny', Meeting::class)) {
            // Admins can see all meetings
        } else {
            // Regular users can only see meetings they organize or are invited to
            $query->where(function ($q) use ($user) {
                $q->where('organizer_id', $user->id)
                  ->orWhereHas('participants', fn($subQ) => $subQ->where('user_id', $user->id));
            });
        }

        if ($request->has('topic')) {
            $query->where('topic', 'like', '%' . $request->input('topic') . '%');
        }

        if ($request->has('start_time')) {
            $query->whereDate('start_time', $request->input('start_time'));
        }

        if ($request->has('location')) {
            $query->whereHas('location', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->input('location') . '%');
            });
        }

        if ($request->has('type') && $request->input('type') !== 'all') {
            $query->where('type', $request->input('type'));
        }

        $perPage = $request->input('per_page', 15);
        return MeetingListItemResource::collection($query->latest()->paginate($perPage));
    }

    /**
     * Fetch meetings for a specific date range for calendar view.
     */
    public function calendar(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $meetings = Meeting::with(['organizer', 'location', 'zoomMeeting'])
            ->whereBetween('start_time', [$validated['start_date'], $validated['end_date']])
            ->latest()
            ->get();

        return MeetingResource::collection($meetings);
    }

    /**
     * Fetch public meetings for a specific date range.
     */
    public function publicCalendar(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $meetings = Meeting::with('location')
            ->whereBetween('start_time', [$validated['start_date'], $validated['end_date']])
            ->latest()
            ->get();

        return PublicMeetingResource::collection($meetings);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Meeting::class);
        $validated = $this->validateStoreRequest($request->all());
        $validated['organizer_id'] = auth()->id();

        $meeting = $this->meetingService->createMeeting($validated);

        return (new MeetingResource($meeting->load(['organizer', 'location', 'zoomMeeting', 'participants'])))
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
            'start_time' => ['required', 'date'],
            'duration' => 'required|integer|min:1',
            'type' => ['required', Rule::in(['online', 'offline', 'hybrid'])],
            'location_id' => [
                'nullable',
                Rule::requiredIf(fn () => in_array($data['type'] ?? null, ['offline', 'hybrid'])),
                'exists:meeting_locations,id',
            ],
            'password' => 'nullable|string|max:10', // Zoom password validation
            'settings' => 'nullable|array', // For Zoom settings
            'participants' => 'nullable|array',
            'participants.*' => 'integer|exists:users,id',
        ])->validate();
    }

    /**
     * Display the specified resource.
     */
    public function show(Meeting $meeting)
    {
        $this->authorize('view', $meeting);
        return new MeetingResource($meeting->load(['organizer', 'location', 'zoomMeeting', 'participants']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Meeting $meeting)
    {
        $this->authorize('delete', $meeting);
        $this->meetingService->deleteMeeting($meeting);

        return response()->json(['message' => 'Meeting deleted successfully.'], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Meeting $meeting)
    {
        $this->authorize('update', $meeting);

        $validated = $request->validate([
            'topic' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'start_time' => 'sometimes|required|date',
            'duration' => 'sometimes|required|integer|min:1',
            'location_id' => 'nullable|exists:meeting_locations,id',
            'settings' => 'nullable|array',
        ]);

        $updatedMeeting = $this->meetingService->updateMeeting($meeting, $validated);

        return new MeetingResource($updatedMeeting->load(['organizer', 'location', 'zoomMeeting']));
    }

    /**
     * List participants for a meeting.
     */
    public function listParticipants(Meeting $meeting)
    {
        $this->authorize('view', $meeting);
        return UserResource::collection($meeting->participants);
    }

    /**
     * Invite a user to a meeting.
     */
    public function invite(Request $request, Meeting $meeting)
    {
        $this->authorize('manageParticipants', $meeting);

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $meeting->participants()->syncWithoutDetaching($validated['user_id']);

        return response()->json(['message' => 'User invited successfully.']);
    }

    /**
     * Remove a participant from a meeting.
     */
    public function removeParticipant(Meeting $meeting, User $user)
    {
        $this->authorize('manageParticipants', $meeting);

        $meeting->participants()->detach($user->id);

        return response()->json(['message' => 'Participant removed successfully.']);
    }
}
