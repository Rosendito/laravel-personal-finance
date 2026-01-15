<?php

declare(strict_types=1);

use App\Enums\ExchangeSourceKey;
use App\Services\ExchangeRates\Fetchers\BcvRateFetcher;
use App\Services\ExchangeRates\Fetchers\BinanceRateFetcher;
use App\Services\ExchangeRates\RateCalculators\BestPriceRateCalculator;

return [
    'currency' => [
        'default' => env('APP_CURRENCY', 'USDT'),
    ],

    'exchange_rates' => [
        /**
         * Map ExchangeSource->key to a fetcher class that implements
         * App\Contracts\ExchangeRates\ExchangeRateFetcher.
         *
         * Example:
         * 'bcv' => \App\Services\ExchangeRates\Fetchers\BcvRateFetcher::class,
         */
        'fetchers' => [
            ExchangeSourceKey::BCV->value => BcvRateFetcher::class,
            ExchangeSourceKey::BINANCE_P2P->value => BinanceRateFetcher::class,
        ],

        /**
         * Optional default / overrides for rate calculation (used by sources like Binance P2P).
         * In practice you may prefer DB-driven policies (e.g. in ExchangeSource.metadata).
         */
        'rate_calculators' => [
            'default' => null,
            'by_source' => [
                ExchangeSourceKey::BINANCE_P2P->value => BestPriceRateCalculator::class,
            ],
            'by_pair' => [
                //
            ],
        ],
    ],
];
