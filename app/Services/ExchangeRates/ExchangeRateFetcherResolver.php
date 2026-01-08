<?php

declare(strict_types=1);

namespace App\Services\ExchangeRates;

use App\Contracts\ExchangeRates\ExchangeRateFetcher;
use App\Exceptions\ExchangeRatesException;
use App\Models\ExchangeSource;
use Illuminate\Contracts\Container\Container;

final readonly class ExchangeRateFetcherResolver
{
    public function __construct(
        private Container $container,
    ) {}

    public function resolve(ExchangeSource $source): ExchangeRateFetcher
    {
        /** @var array<string, class-string> $fetchers */
        $fetchers = (array) config('finance.exchange_rates.fetchers', []);

        $sourceKey = (string) $source->key;
        $fetcherClass = $fetchers[$sourceKey] ?? null;

        if (! is_string($fetcherClass) || $fetcherClass === '') {
            throw ExchangeRatesException::fetcherNotConfigured($sourceKey);
        }

        $fetcher = $this->container->make($fetcherClass);

        if (! $fetcher instanceof ExchangeRateFetcher) {
            throw ExchangeRatesException::invalidFetcher($sourceKey, $fetcherClass);
        }

        return $fetcher;
    }
}
