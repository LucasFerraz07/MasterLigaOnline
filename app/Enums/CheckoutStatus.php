<?php

namespace App\Enums;

enum CheckoutStatus: string
{
    case OPEN = 'open';
    case PAYMENT_PENDING = 'payment_pending';
    case PAID = 'paid';
    case FULFILLED = 'fulfilled';
    case EXPIRED = 'expired';
    case CANCELED = 'canceled';
    case REQUIRES_ACTION = 'requires_action';
}
