<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case YET_TO_PROCESS = 'YET_TO_PROCESS';
    case INITIATED = 'INITIATED';
    case SUCCESS = 'SUCCESS';
    case FAILED = 'FAILED';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $status) => $status->value, self::cases());
    }
}
