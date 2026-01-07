<?php

declare(strict_types=1);

namespace App\Enums;

enum ExchangeSourceKey: string
{
    case BCV = 'bcv';

    case BINANCE_P2P = 'binance_p2p';
}
