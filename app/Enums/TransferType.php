<?php

namespace App\Enums;

enum TransferType: string
{
    case Negotiation = 'negotiation';
    case Mulct = 'Mulct';
    case Free = 'free';
}
