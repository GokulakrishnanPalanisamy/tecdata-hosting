<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PENDING = 'PENDING';
    case PAYMENT_PROCESSING = 'PAYMENT_PROCESSING';
    case PAID = 'PAID';
    case FAILED = 'FAILED';
    case CANCELLED = 'CANCELLED';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $status) => $status->value, self::cases());
    }
}
