<?php

namespace App\Enums;

enum CheckoutType: string
{
    case INITIAL = 'initial';
    case RENEWAL = 'renewal';
}
