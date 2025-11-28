<?php

declare(strict_types=1);

namespace App\Actions\Currencies;

use App\Data\Currencies\ExchangeRateData;
use Illuminate\Support\Facades\Cache;

final class FetchUsdtVesRateAction
{
    private const CACHE_KEY_PREFIX = 'usdt_ves_rate';

    private const CACHE_TTL = 300;

    public function __construct(
        private readonly FetchBinanceRateAction $fetchBinanceRateAction,
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

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($transAmount, $rows): ExchangeRateData {
            return $this->fetchRate($transAmount, $rows);
        });
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
