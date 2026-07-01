<?php

namespace App\Enums;

enum MatchStatus: string
{
    case Pending = 'pending';
    case Finished = 'finished';
}
