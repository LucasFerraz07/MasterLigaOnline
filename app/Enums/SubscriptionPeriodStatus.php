<?php

namespace App\Enums;

enum SubscriptionPeriodStatus: string
{
    case ACTIVE = 'active';
    case SCHEDULED = 'scheduled';
    case EXPIRED = 'expired';
    case REVOKED = 'revoked';
}
