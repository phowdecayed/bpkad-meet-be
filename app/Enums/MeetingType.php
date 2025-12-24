<?php

namespace App\Enums;

enum MeetingType: string
{
    case ONLINE = 'online';
    case OFFLINE = 'offline';
    case HYBRID = 'hybrid';
}
