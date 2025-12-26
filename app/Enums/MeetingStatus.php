<?php

namespace App\Enums;

enum MeetingStatus: string
{
    case SCHEDULED = 'scheduled';
    case STARTED = 'started';
    case FINISHED = 'finished';
    case CANCELED = 'canceled';

    public function label(): string
    {
        return match ($this) {
            self::SCHEDULED => 'Terjadwal',
            self::STARTED => 'Berlangsung',
            self::FINISHED => 'Selesai',
            self::CANCELED => 'Dibatalkan',
        };
    }
}
