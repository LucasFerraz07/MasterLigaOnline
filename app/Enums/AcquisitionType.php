<?php

namespace App\Enums;

enum AcquisitionType: string
{
    case Initial = 'initial';
    case Negotiation = 'negotiation';
    case Mulct = 'Mulct';
}