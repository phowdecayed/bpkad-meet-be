<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MeetingLocationResource;
use App\Models\MeetingLocation;
use Illuminate\Http\Request;

class MeetingLocationController extends Controller
{
    public function __construct()
    {
        //
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return MeetingLocationResource::collection(MeetingLocation::all());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'room_name' => 'nullable|string|max:255',
            'capacity' => 'nullable|integer|min:1',
        ]);

        $location = MeetingLocation::create($validated);

        return (new MeetingLocationResource($location))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(MeetingLocation $meetingLocation)
    {
        return new MeetingLocationResource($meetingLocation);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MeetingLocation $meetingLocation)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'address' => 'sometimes|required|string|max:255',
            'room_name' => 'nullable|string|max:255',
            'capacity' => 'nullable|integer|min:1',
        ]);

        $meetingLocation->update($validated);

        return new MeetingLocationResource($meetingLocation);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MeetingLocation $meetingLocation)
    {
        $meetingLocation->delete();

        return response()->json(['message' => 'Meeting location deleted successfully.'], 200);
    }
}
