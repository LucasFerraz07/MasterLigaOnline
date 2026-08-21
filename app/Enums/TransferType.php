<?php

namespace App\Enums;

enum TransferType: string
{
    case Negotiation = 'negotiation';
    case Mulct = 'mulct';
    case Free = 'free';
    case Release = 'release';
}
