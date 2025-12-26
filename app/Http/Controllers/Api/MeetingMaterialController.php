<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Meeting;
use App\Models\MeetingMaterial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MeetingMaterialController extends Controller
{
    /**
     * Upload a material file for a meeting.
     */
    public function store(Request $request, Meeting $meeting)
    {
        if ($request->user()->cannot('update', $meeting)) {
            abort(403);
        }

        $request->validate([
            'file' => 'required|file|mimes:pdf,doc,docx,ppt,pptx,jpg,png,jpeg|max:10240', // 10MB
        ]);

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $fileType = $file->getMimeType();

        // Store in 'materials' folder within public disk
        $path = $file->store('materials', 'public');

        $material = $meeting->materials()->create([
            'file_path' => $path,
            'original_name' => $originalName,
            'file_type' => $fileType,
        ]);

        return response()->json($material, 201);
    }

    /**
     * Delete a material file.
     */
    public function destroy(Request $request, MeetingMaterial $material)
    {
        if ($request->user()->cannot('update', $material->meeting)) {
            abort(403);
        }

        // Delete from storage
        Storage::disk('public')->delete($material->file_path);

        // Delete from DB
        $material->delete();

        return response()->noContent();
    }
}
