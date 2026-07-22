<?php

namespace App\Enums;

enum SubscriptionPeriodStatus: string
{
    case ACTIVE = 'active';
    case EXPIRED = 'expired';
    case CANCELED = 'canceled';
}
