<?php

declare(strict_types=1);

namespace App\Actions\ExchangeRates;

use App\Data\ExchangeRates\FetchedExchangeRateData;
use App\Data\ExchangeRates\RequestedPairsData;
use App\Exceptions\ExchangeRatesException;
use App\Models\ExchangeCurrencyPair;
use App\Models\ExchangeRate;
use App\Models\ExchangeSource;
use App\Services\ExchangeRates\ExchangeRateFetcherResolver;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final readonly class SyncExchangeRatesAction
{
    public function __construct(
        private ExchangeRateFetcherResolver $fetcherResolver,
    ) {}

    /**
     * @return Collection<int, ExchangeRate>
     */
    public function execute(ExchangeSource $source, ?RequestedPairsData $requestedPairs = null): Collection
    {
        $requestedPairs ??= RequestedPairsData::forAllPairs();

        $this->assertRequestedPairsSupported($source, $requestedPairs);

        $fetcher = $this->fetcherResolver->resolve($source);
        $fetchedRates = $fetcher->fetch($source, $requestedPairs);

        if (! $requestedPairs->isAll()) {
            $requestedKeys = $requestedPairs->pairKeys();

            $fetchedRates = $fetchedRates
                ->filter(fn (FetchedExchangeRateData $rate): bool => in_array($rate->pairKey(), $requestedKeys, true))
                ->values();

            $missing = array_values(array_diff($requestedKeys, $fetchedRates->map(fn (FetchedExchangeRateData $rate): string => $rate->pairKey())->all()));

            if ($missing !== []) {
                throw ExchangeRatesException::missingRequestedPairs((string) $source->key, $missing);
            }
        }

        return DB::transaction(fn (): Collection => $fetchedRates
            ->map(fn (FetchedExchangeRateData $rate): ExchangeRate => $this->persistRate($source, $rate))
            ->values());
    }

    private function persistRate(ExchangeSource $source, FetchedExchangeRateData $rate): ExchangeRate
    {
        $pair = ExchangeCurrencyPair::query()
            ->where('base_currency_code', $rate->baseCurrencyCode)
            ->where('quote_currency_code', $rate->quoteCurrencyCode)
            ->first();

        if (! $pair instanceof ExchangeCurrencyPair) {
            throw ExchangeRatesException::pairNotFound($rate->pairKey());
        }

        $this->assertPairSupported($source, $pair);

        return ExchangeRate::query()->updateOrCreate(
            [
                'exchange_currency_pair_id' => $pair->id,
                'exchange_source_id' => $source->id,
                'effective_at' => $rate->effectiveAt,
            ],
            [
                'rate' => $rate->rate,
                'retrieved_at' => $rate->retrievedAt,
                'is_estimated' => $rate->isEstimated,
                'meta' => $rate->metadata,
            ],
        );
    }

    private function assertRequestedPairsSupported(ExchangeSource $source, RequestedPairsData $requestedPairs): void
    {
        if ($requestedPairs->isAll()) {
            return;
        }

        /** @var Collection<int, ExchangeCurrencyPair> $pairs */
        $pairs = $requestedPairs->pairs ?? collect();

        foreach ($pairs as $pair) {
            $this->assertPairSupported($source, $pair);
        }
    }

    private function assertPairSupported(ExchangeSource $source, ExchangeCurrencyPair $pair): void
    {
        $pairKey = "{$pair->base_currency_code}/{$pair->quote_currency_code}";

        if (! $pair->isSupportedBy($source)) {
            throw ExchangeRatesException::unsupportedPair((string) $source->key, $pairKey);
        }
    }
}
