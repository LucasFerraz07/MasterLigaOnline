<?php

namespace App\Enums;

enum SeasonStatus: string
{
    case Open = 'open';
    case Active = 'active';
    case Closed = 'closed';
}