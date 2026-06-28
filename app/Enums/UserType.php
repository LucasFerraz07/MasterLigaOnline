<?php

namespace App\Enums;

enum UserType: string
{
    case SYSTEM_ADMIN = 'system_admin';
    case TENANT_ADMIN = 'tenant_admin';
    case USER = 'user';
}
