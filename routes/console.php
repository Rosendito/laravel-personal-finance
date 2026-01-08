<?php

declare(strict_types=1);

use App\Enums\ExchangeSourceKey;
use Illuminate\Support\Facades\Schedule;

$syncExchangeRatesBinanceP2PCommand = 'exchange-rates:sync --source='.ExchangeSourceKey::BINANCE_P2P->value;
$syncExchangeRatesBcvCommand = 'exchange-rates:sync --source='.ExchangeSourceKey::BCV->value;
$timezone = 'America/Caracas';

Schedule::command($syncExchangeRatesBinanceP2PCommand)
    ->everyFiveMinutes()
    ->withoutOverlapping();

Schedule::command($syncExchangeRatesBcvCommand)
    ->timezone($timezone)
    ->everyFiveMinutes()
    ->between('09:00', '09:30')
    ->withoutOverlapping();

Schedule::command($syncExchangeRatesBcvCommand)
    ->timezone($timezone)
    ->everyFiveMinutes()
    ->between('13:00', '13:30')
    ->withoutOverlapping();

Schedule::command($syncExchangeRatesBcvCommand)
    ->timezone($timezone)
    ->hourly()
    ->unlessBetween('08:55', '09:35')
    ->unlessBetween('12:55', '13:35')
    ->withoutOverlapping(10);
