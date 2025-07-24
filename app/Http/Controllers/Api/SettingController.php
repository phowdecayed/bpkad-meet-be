<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Setting::query();

        if ($request->has('group')) {
            $query->where('group', $request->group);
        }

        return response()->json($query->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:settings,name',
            'group' => 'sometimes|string',
            'payload' => 'required|array',
        ]);

        $setting = Setting::create($validated);

        return response()->json($setting, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Setting $setting)
    {
        return response()->json($setting);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Setting $setting)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|unique:settings,name,' . $setting->id,
            'group' => 'sometimes|string',
            'payload' => 'sometimes|array',
        ]);

        $setting->update($validated);

        return response()->json($setting);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Setting $setting)
    {
        $setting->delete();

        return response()->json(['message' => 'Setting deleted successfully.']);
    }
}