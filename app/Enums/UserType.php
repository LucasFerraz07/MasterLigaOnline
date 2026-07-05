<?php

namespace App\Enums;

enum UserType: string
{
    case SYSTEM_ADMIN = 'system_admin';
    case LEAGUE_ADMIN = 'league_admin';
    case USER = 'user';
}
