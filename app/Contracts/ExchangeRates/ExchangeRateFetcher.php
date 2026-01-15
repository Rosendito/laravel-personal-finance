<?php

declare(strict_types=1);

namespace App\Contracts\ExchangeRates;

use App\Data\ExchangeRates\FetchedExchangeRateData;
use App\Data\ExchangeRates\RequestedPairsData;
use App\Models\ExchangeSource;
use Illuminate\Support\Collection;

interface ExchangeRateFetcher
{
    /**
     * @return Collection<int, FetchedExchangeRateData>
     */
    public function fetch(ExchangeSource $source, ?RequestedPairsData $requestedPairs = null): Collection;
}
