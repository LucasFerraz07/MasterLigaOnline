<?php

namespace App\Enums;

enum UserRole: string
{
    case CO_OWNER = 'co-owner';
    case DEFAULT = 'default';
}
