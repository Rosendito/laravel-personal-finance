<?php

declare(strict_types=1);

namespace Tests\Mocks\ExchangeRates;

use App\Contracts\ExchangeRates\ExchangeRateFetcher;
use App\Data\ExchangeRates\FetchedExchangeRateData;
use App\Data\ExchangeRates\RequestedPairsData;
use App\Models\ExchangeSource;
use Illuminate\Support\Collection;

final class FakeUsdVesFetcher implements ExchangeRateFetcher
{
    public function fetch(ExchangeSource $source, ?RequestedPairsData $requestedPairs = null): Collection
    {
        return collect([
            new FetchedExchangeRateData(
                baseCurrencyCode: 'USD',
                quoteCurrencyCode: 'VES',
                rate: '36.500000000000000000',
                effectiveAt: now(),
                retrievedAt: now(),
                isEstimated: false,
                metadata: [
                    'strategy' => 'fixed',
                ],
            ),
        ]);
    }
}
