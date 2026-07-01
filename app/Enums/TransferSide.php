<?php

namespace App\Enums;

enum TransferSide: string
{
    case Proposer = 'proposer';
    case Receiver = 'receiver';
}
