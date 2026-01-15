<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class ExchangeRatesException extends RuntimeException
{
    public static function fetcherNotConfigured(string $sourceKey): self
    {
        return new self("No exchange rate fetcher configured for source [{$sourceKey}].");
    }

    public static function invalidFetcher(string $sourceKey, string $class): self
    {
        return new self("Configured fetcher [{$class}] for source [{$sourceKey}] is invalid.");
    }

    public static function rateCalculatorNotConfigured(string $sourceKey, string $pairKey): self
    {
        return new self("No rate calculator configured for source [{$sourceKey}] and pair [{$pairKey}].");
    }

    public static function invalidRateCalculator(string $sourceKey, string $pairKey, string $class): self
    {
        return new self("Configured rate calculator [{$class}] for source [{$sourceKey}] and pair [{$pairKey}] is invalid.");
    }

    public static function unsupportedPair(string $sourceKey, string $pairKey): self
    {
        return new self("Pair [{$pairKey}] is not supported by source [{$sourceKey}].");
    }

    /**
     * @param  array<int, string>  $pairKeys
     */
    public static function missingRequestedPairs(string $sourceKey, array $pairKeys): self
    {
        $pairs = implode(', ', $pairKeys);

        return new self("Fetcher for source [{$sourceKey}] did not return all requested pairs: {$pairs}.");
    }

    public static function pairNotFound(string $pairKey): self
    {
        return new self("Exchange currency pair [{$pairKey}] was not found in the database.");
    }

    public static function sourceNotFound(int $sourceId): self
    {
        return new self("Exchange source [{$sourceId}] was not found in the database.");
    }

    /**
     * @param  array<int, int>  $pairIds
     */
    public static function pairsNotFound(array $pairIds): self
    {
        $ids = implode(', ', $pairIds);

        return new self("One or more exchange currency pairs were not found in the database. Requested ids: {$ids}.");
    }
}
