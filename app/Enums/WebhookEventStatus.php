<?php

namespace App\Enums;

enum WebhookEventStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case PROCESSED = 'processed';
    case FAILED = 'failed';
    case IGNORED = 'ignored';
}
