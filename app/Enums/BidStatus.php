<?php

namespace App\Enums;

enum BidStatus: string
{
    case Pending = 'pending';
    case Rejected = 'rejected';
    case Accepted = 'accepted';
    case Cancelled = 'cancelled';
}