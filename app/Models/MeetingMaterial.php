<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class MeetingMaterial extends Model
{
    use HasFactory;

    protected $fillable = [
        'meeting_id',
        'file_path',
        'original_name',
        'file_type',
    ];

    /**
     * Get the meeting that owns the material.
     */
    public function meeting()
    {
        return $this->belongsTo(Meeting::class);
    }

    /**
     * Get the download URL.
     */
    public function getDownloadUrlAttribute()
    {
        return Storage::url($this->file_path);
    }
}
