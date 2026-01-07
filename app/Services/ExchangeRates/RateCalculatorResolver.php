<?php

declare(strict_types=1);

namespace App\Services\ExchangeRates;

use App\Contracts\ExchangeRates\RateCalculator;
use App\Exceptions\ExchangeRatesException;
use App\Models\ExchangeCurrencyPair;
use App\Models\ExchangeSource;
use Illuminate\Contracts\Container\Container;

final readonly class RateCalculatorResolver
{
    public function __construct(
        private Container $container,
    ) {}

    public function resolve(ExchangeSource $source, ExchangeCurrencyPair $pair): RateCalculator
    {
        $sourceKey = (string) $source->key;
        $pairKey = "{$pair->base_currency_code}/{$pair->quote_currency_code}";

        $metadataClass = data_get($source->metadata, "rate_calculators.by_pair.{$pairKey}", data_get($source->metadata, 'rate_calculators.default'));

        $configClass = config("finance.exchange_rates.rate_calculators.by_pair.{$sourceKey}.{$pairKey}", config("finance.exchange_rates.rate_calculators.by_source.{$sourceKey}", config('finance.exchange_rates.rate_calculators.default')));

        $calculatorClass = $metadataClass ?? $configClass;

        if (! is_string($calculatorClass) || $calculatorClass === '') {
            throw ExchangeRatesException::rateCalculatorNotConfigured($sourceKey, $pairKey);
        }

        $calculator = $this->container->make($calculatorClass);

        if (! $calculator instanceof RateCalculator) {
            throw ExchangeRatesException::invalidRateCalculator($sourceKey, $pairKey, $calculatorClass);
        }

        return $calculator;
    }
}
