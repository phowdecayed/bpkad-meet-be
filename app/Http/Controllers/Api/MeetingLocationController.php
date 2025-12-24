<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMeetingLocationRequest;
use App\Http\Requests\UpdateMeetingLocationRequest;
use App\Http\Resources\MeetingLocationResource;
use App\Models\MeetingLocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MeetingLocationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        return MeetingLocationResource::collection(MeetingLocation::all());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMeetingLocationRequest $request): JsonResponse
    {
        $location = MeetingLocation::create($request->validated());

        return (new MeetingLocationResource($location))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(MeetingLocation $meetingLocation): MeetingLocationResource
    {
        return new MeetingLocationResource($meetingLocation);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMeetingLocationRequest $request, MeetingLocation $meetingLocation): MeetingLocationResource
    {
        $meetingLocation->update($request->validated());

        return new MeetingLocationResource($meetingLocation);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MeetingLocation $meetingLocation): JsonResponse
    {
        $meetingLocation->delete();

        return response()->json(['message' => 'Meeting location deleted successfully.'], 200);
    }
}
