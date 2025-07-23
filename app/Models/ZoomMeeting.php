<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ZoomMeeting extends Model
{
    use HasFactory;

    protected $fillable = [
        'zoom_id',
        'uuid',
        'host_id',
        'host_email',
        'topic',
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
    ];

    protected $casts = [
        'settings' => 'array',
        'start_time' => 'datetime',
        'created_at_zoom' => 'datetime',
    ];
}
