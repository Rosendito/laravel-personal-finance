<?php

declare(strict_types=1);

namespace App\Actions\Currencies;

use App\Data\Currencies\ExchangeRateData;
use Illuminate\Support\Facades\Cache;

final readonly class FetchUsdtVesRateAction
{
    private const string CACHE_KEY_PREFIX = 'usdt_ves_rate';

    private const int CACHE_TTL = 300;

    public function __construct(
        private FetchBinanceRateAction $fetchBinanceRateAction,
    ) {}

    public function execute(
        int|float|string $transAmount = 10000,
        int $rows = 10,
        bool $force = false,
    ): ExchangeRateData {
        if ($force) {
            return $this->fetchRate($transAmount, $rows);
        }

        $cacheKey = $this->buildCacheKey($transAmount, $rows);

        return Cache::remember($cacheKey, self::CACHE_TTL, fn (): ExchangeRateData => $this->fetchRate($transAmount, $rows));
    }

    private function fetchRate(int|float|string $transAmount, int $rows): ExchangeRateData
    {
        return $this->fetchBinanceRateAction->execute(
            transAmount: $transAmount,
            rows: $rows,
            sellAsset: 'USDT',
            buyFiat: 'VES',
            tradeType: 'SELL',
        );
    }

    private function buildCacheKey(int|float|string $transAmount, int $rows): string
    {
        return self::CACHE_KEY_PREFIX.":{$transAmount}:{$rows}";
    }
}
