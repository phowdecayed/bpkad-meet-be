<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Meeting extends Model
{
    use HasFactory;

    protected $fillable = [
        'topic',
        'description',
        'start_time',
        'duration',
        'type',
        'location_id',
    ];

    protected $casts = [
        'start_time' => 'datetime',
    ];

    public function location()
    {
        return $this->belongsTo(MeetingLocation::class);
    }

    public function zoomMeeting()
    {
        return $this->hasOne(ZoomMeeting::class);
    }
}