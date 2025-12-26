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
        'status',
        'location_id',
        'notulen',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'type' => MeetingType::class,
            'status' => \App\Enums\MeetingStatus::class,
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    protected $with = ['zoomMeeting.setting'];

    public function setStartTimeAttribute($value)
    {
        $this->attributes['start_time'] = Carbon::parse($value);
    }

    public function organizer()
    {
        return $this->belongsTo(User::class, 'organizer_id')->withTrashed();
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

    public function attendances()
    {
        return $this->hasMany(MeetingAttendance::class);
    }

    public function materials()
    {
        return $this->hasMany(MeetingMaterial::class);
    }
}
