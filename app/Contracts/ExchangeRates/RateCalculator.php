<?php

declare(strict_types=1);

namespace App\Contracts\ExchangeRates;

use App\Models\ExchangeCurrencyPair;
use App\Models\ExchangeSource;
use Illuminate\Support\Collection;

interface RateCalculator
{
    /**
     * @param  Collection<int, array<string, mixed>>  $quotes  Normalized quotes (source-specific payload normalized to a shared shape).
     * @return array{rate: string, metadata: array<string, mixed>}
     */
    public function calculate(ExchangeSource $source, ExchangeCurrencyPair $pair, Collection $quotes): array;
}
