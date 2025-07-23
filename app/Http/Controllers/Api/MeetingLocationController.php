<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MeetingLocation;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MeetingLocationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return MeetingLocation::all();
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

        return response()->json($location, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(MeetingLocation $meetingLocation)
    {
        return $meetingLocation;
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

        // Refresh the model to get the updated data
        $meetingLocation->refresh();

        return response()->json($meetingLocation);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MeetingLocation $meetingLocation)
    {
        $meetingLocation->delete();

        return response()->json(null, 204);
    }
}