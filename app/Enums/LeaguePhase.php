<?php

namespace App\Enums;

enum LeaguePhase: string
{
    case WindowOpening = 'window_opening';
    case FirstHalf = 'first_half';
    case MidWindow = 'mid_window';
    case SecondHalf = 'second_half';
}