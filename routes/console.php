<?php

declare(strict_types=1);

use App\Enums\ExchangeSourceKey;
use Illuminate\Support\Facades\Schedule;

$syncExchangeRatesBinanceP2PCommand = 'exchange-rates:sync --source='.ExchangeSourceKey::BINANCE_P2P->value;
$syncExchangeRatesBcvCommand = 'exchange-rates:sync --source='.ExchangeSourceKey::BCV->value;
$timezone = 'America/Caracas';

Schedule::command($syncExchangeRatesBcvCommand)
    ->name('exchange-rates:sync-bcv')
    ->timezone($timezone)
    ->everyFiveMinutes();

Schedule::command($syncExchangeRatesBinanceP2PCommand)
    ->name('exchange-rates:sync-binance-p2p')
    ->timezone($timezone)
    ->everyFiveMinutes();
