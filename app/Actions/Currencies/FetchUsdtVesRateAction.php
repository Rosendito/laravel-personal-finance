<?php

declare(strict_types=1);

namespace App\Actions\Currencies;

use App\Data\Currencies\ExchangeRateData;

final class FetchUsdtVesRateAction
{
    public function __construct(
        private readonly FetchBinanceRateAction $fetchBinanceRateAction,
    ) {}

    public function execute(
        int|float|string $transAmount = 10000,
        int $rows = 10,
    ): ExchangeRateData {
        return $this->fetchBinanceRateAction->execute(
            transAmount: $transAmount,
            rows: $rows,
            sellAsset: 'USDT',
            buyFiat: 'VES',
            tradeType: 'BUY',
        );
    }
}
