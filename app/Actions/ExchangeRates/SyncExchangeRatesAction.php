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
use Brick\Math\BigDecimal;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

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

        $latestRate = ExchangeRate::query()
            ->where('exchange_currency_pair_id', $pair->id)
            ->where('exchange_source_id', $source->id)
            ->latest('effective_at')
            ->latest('retrieved_at')
            ->orderByDesc('id')
            ->first();

        if ($latestRate instanceof ExchangeRate && $this->isSameRate((string) $latestRate->rate, $rate->rate)) {
            return $latestRate;
        }

        return ExchangeRate::query()->create([
            'exchange_currency_pair_id' => $pair->id,
            'exchange_source_id' => $source->id,
            'rate' => $rate->rate,
            'effective_at' => $rate->effectiveAt,
            'retrieved_at' => $rate->retrievedAt,
            'is_estimated' => $rate->isEstimated,
            'meta' => $rate->metadata,
        ]);
    }

    private function isSameRate(string $a, string $b): bool
    {
        $a = mb_trim($a);
        $b = mb_trim($b);

        if ($a === '' || $b === '') {
            return false;
        }

        try {
            return BigDecimal::of($a)->isEqualTo(BigDecimal::of($b));
        } catch (Throwable) {
            return $a === $b;
        }
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
