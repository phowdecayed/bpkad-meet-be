<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMeetingRequest;
use App\Http\Requests\UpdateMeetingRequest;
use App\Http\Resources\MeetingListItemResource;
use App\Http\Resources\MeetingResource;
use App\Http\Resources\PublicMeetingResource;
use App\Http\Resources\UserResource;
use App\Models\Meeting;
use App\Models\User;
use App\Services\MeetingService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

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
    public function index(Request $request): AnonymousResourceCollection
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
                    ->orWhereHas('participants', fn ($subQ) => $subQ->where('user_id', $user->id));
            });
        }

        if ($request->has('topic')) {
            $query->where('topic', 'like', '%'.$request->input('topic').'%');
        }

        if ($request->has('start_time')) {
            $query->whereDate('start_time', $request->input('start_time'));
        }

        if ($request->has('location')) {
            $query->whereHas('location', function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->input('location').'%');
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
    public function calendar(Request $request): AnonymousResourceCollection
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
    public function publicCalendar(Request $request): AnonymousResourceCollection
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
    public function store(StoreMeetingRequest $request): JsonResponse
    {
        $this->authorize('create', Meeting::class);
        $validated = $request->validated();
        $validated['organizer_id'] = auth()->id();

        $meeting = $this->meetingService->createMeeting($validated);

        return (new MeetingResource($meeting->load(['organizer', 'location', 'zoomMeeting', 'participants'])))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Meeting $meeting): MeetingResource
    {
        $this->authorize('view', $meeting);

        return new MeetingResource($meeting->load(['organizer', 'location', 'zoomMeeting', 'participants']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Meeting $meeting): JsonResponse
    {
        $this->authorize('delete', $meeting);
        $this->meetingService->deleteMeeting($meeting);

        return response()->json(['message' => 'Meeting deleted successfully.'], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMeetingRequest $request, Meeting $meeting): MeetingResource
    {
        $this->authorize('update', $meeting);

        $validated = $request->validated();

        $updatedMeeting = $this->meetingService->updateMeeting($meeting, $validated);

        return new MeetingResource($updatedMeeting->load(['organizer', 'location', 'zoomMeeting']));
    }

    /**
     * List participants for a meeting.
     */
    public function listParticipants(Meeting $meeting): AnonymousResourceCollection
    {
        $this->authorize('view', $meeting);

        return UserResource::collection($meeting->participants);
    }

    /**
     * Invite a user to a meeting.
     */
    public function invite(Request $request, Meeting $meeting): JsonResponse
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
    public function removeParticipant(Meeting $meeting, User $user): JsonResponse
    {
        $this->authorize('manageParticipants', $meeting);

        $meeting->participants()->detach($user->id);

        return response()->json(['message' => 'Participant removed successfully.']);
    }
}
