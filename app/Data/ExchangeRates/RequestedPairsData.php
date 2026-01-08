<?php

declare(strict_types=1);

namespace App\Data\ExchangeRates;

use App\Models\ExchangeCurrencyPair;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

final class RequestedPairsData extends Data
{
    /**
     * @param  Collection<int, ExchangeCurrencyPair>|null  $pairs
     */
    public function __construct(
        public ?Collection $pairs = null,
    ) {}

    public static function forAllPairs(): self
    {
        return new self();
    }

    public static function forPairs(ExchangeCurrencyPair ...$pairs): self
    {
        return new self(collect($pairs));
    }

    public function isAll(): bool
    {
        return ! $this->pairs instanceof Collection;
    }

    /**
     * @return array<int, string>
     */
    public function pairKeys(): array
    {
        if (! $this->pairs instanceof Collection) {
            return [];
        }

        return $this->pairs
            ->map(fn (ExchangeCurrencyPair $pair): string => "{$pair->base_currency_code}/{$pair->quote_currency_code}")
            ->values()
            ->all();
    }
}
