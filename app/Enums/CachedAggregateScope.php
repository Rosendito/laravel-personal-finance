<?php

declare(strict_types=1);

namespace App\Enums;

enum CachedAggregateScope: string
{
    case Monthly = 'monthly';
    case Daily = 'daily';
}
