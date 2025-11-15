<?php

declare(strict_types=1);

namespace App\Enums;

enum CachedAggregateKey: string
{
    case CurrentBalance = 'current_balance';
    case Spent = 'spent';
}
