<?php

namespace App\Enums;

enum LeaguePhase: string
{
    case WindowOpening = 'window_opening';
    case FirstWindow = 'first_window';
    case FirstHalf = 'first_half';
    case MidWindow = 'mid_window';
    case SecondHalf = 'second_half';
    case Ended = 'ended';
}
