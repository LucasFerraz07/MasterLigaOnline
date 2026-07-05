<?php

namespace App\Enums;

enum TransactionOperation: string
{
    case Credit = 'credit';
    case Debit = 'debit';
}