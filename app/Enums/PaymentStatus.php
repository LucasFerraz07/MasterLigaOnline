<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case CREATING = 'creating';
    case PENDING = 'pending';
    case IN_PROCESS = 'in_process';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case CANCELED = 'canceled';
    case REFUNDED = 'refunded';
    case UNKNOWN = 'unknown';
    case REQUIRES_ACTION = 'requires_action';
}
