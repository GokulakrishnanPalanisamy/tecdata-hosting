<?php

namespace App\Enums;

enum WebhookEventStatus: string
{
    case RECEIVED = 'RECEIVED';
    case PROCESSING = 'PROCESSING';
    case PROCESSED = 'PROCESSED';
    case FAILED = 'FAILED';
    case DUPLICATE = 'DUPLICATE';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $status) => $status->value, self::cases());
    }
}
