<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ZoomMeeting extends Model
{
    use HasFactory;

    protected $fillable = [
        'meeting_id',
        'zoom_id',
        'uuid',
        'host_id',
        'host_email',
        'type',
        'status',
        'start_time',
        'duration',
        'timezone',
        'created_at_zoom',
        'start_url',
        'join_url',
        'password',
        'settings',
        'setting_id',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'start_time' => 'datetime',
            'created_at_zoom' => 'datetime',
        ];
    }

    public function meeting()
    {
        return $this->belongsTo(Meeting::class);
    }

    public function setting()
    {
        return $this->belongsTo(Setting::class);
    }
}
