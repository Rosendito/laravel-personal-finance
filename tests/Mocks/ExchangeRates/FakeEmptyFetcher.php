<?php

declare(strict_types=1);

namespace Tests\Mocks\ExchangeRates;

use App\Contracts\ExchangeRates\ExchangeRateFetcher;
use App\Data\ExchangeRates\RequestedPairsData;
use App\Models\ExchangeSource;
use Illuminate\Support\Collection;

final class FakeEmptyFetcher implements ExchangeRateFetcher
{
    public function fetch(ExchangeSource $source, ?RequestedPairsData $requestedPairs = null): Collection
    {
        return collect();
    }
}
