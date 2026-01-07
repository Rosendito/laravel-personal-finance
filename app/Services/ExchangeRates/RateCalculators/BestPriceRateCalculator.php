<?php

declare(strict_types=1);

namespace App\Services\ExchangeRates\RateCalculators;

use App\Contracts\ExchangeRates\RateCalculator;
use App\Models\ExchangeCurrencyPair;
use App\Models\ExchangeSource;
use Illuminate\Support\Collection;
use RuntimeException;

final class BestPriceRateCalculator implements RateCalculator
{
    /**
     * @param  Collection<int, array<string, mixed>>  $quotes
     * @return array{rate: string, metadata: array<string, mixed>}
     */
    public function calculate(ExchangeSource $source, ExchangeCurrencyPair $pair, Collection $quotes): array
    {
        $prices = $quotes
            ->map(fn (array $quote): mixed => data_get($quote, 'price'))
            ->filter(fn (mixed $price): bool => is_numeric($price))
            ->map(fn (mixed $price): float => (float) $price)
            ->values();

        throw_if($prices->isEmpty(), RuntimeException::class, 'No numeric prices were provided for rate calculation.');

        $tradeType = $this->resolveTradeType($quotes);
        $min = (float) $prices->min();
        $max = (float) $prices->max();
        $median = $this->median($prices);

        $rateFloat = match ($tradeType) {
            // If the request/ads are BUY, you are buying the asset -> best price is the lowest.
            'BUY' => $min,
            // If the request/ads are SELL, you are selling the asset -> best price is the highest.
            'SELL' => $max,
            default => $median,
        };

        return [
            'rate' => number_format($rateFloat, 8, '.', ''),
            'metadata' => [
                'strategy' => 'best_price',
                'sample_size' => $prices->count(),
                'trade_type' => $tradeType,
                'min' => number_format($min, 8, '.', ''),
                'max' => number_format($max, 8, '.', ''),
                'median' => $median !== null ? number_format($median, 8, '.', '') : null,
            ],
        ];
    }

    private function resolveTradeType(Collection $quotes): ?string
    {
        $types = $quotes
            ->map(fn (array $quote): mixed => data_get($quote, 'trade_type'))
            ->filter(fn (mixed $type): bool => is_string($type) && $type !== '')
            ->map(fn (string $type): string => mb_strtoupper($type))
            ->unique()
            ->values();

        if ($types->count() === 1) {
            return $types->first();
        }

        return null;
    }

    /**
     * @param  Collection<int, float>  $values
     */
    private function median(Collection $values): ?float
    {
        $sorted = $values->sort()->values();
        $count = $sorted->count();

        if ($count === 0) {
            return null;
        }

        $middle = intdiv($count, 2);

        if ($count % 2 === 1) {
            return $sorted->get($middle);
        }

        $a = $sorted->get($middle - 1);
        $b = $sorted->get($middle);

        if (! is_float($a) || ! is_float($b)) {
            return null;
        }

        return ($a + $b) / 2;
    }
}
