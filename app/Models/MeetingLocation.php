<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MeetingLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'room_name',
        'capacity',
    ];

    public function meetings()
    {
        return $this->hasMany(Meeting::class, 'location_id');
    }
}
