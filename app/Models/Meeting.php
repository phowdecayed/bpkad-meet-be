<?php

namespace App\Models;

use App\Enums\MeetingType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Meeting extends Model
{
    use HasFactory;

    protected $fillable = [
        'organizer_id',
        'topic',
        'description',
        'start_time',
        'duration',
        'type',
        'location_id',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'type' => MeetingType::class,
    ];

    protected $with = ['zoomMeeting.setting'];

    public function setStartTimeAttribute($value)
    {
        $this->attributes['start_time'] = Carbon::parse($value);
    }

    public function organizer()
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    public function participants()
    {
        return $this->belongsToMany(User::class);
    }

    public function location()
    {
        return $this->belongsTo(MeetingLocation::class);
    }

    public function zoomMeeting()
    {
        return $this->hasOne(ZoomMeeting::class);
    }
}
