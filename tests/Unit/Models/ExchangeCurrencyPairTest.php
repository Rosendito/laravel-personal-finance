<?php

declare(strict_types=1);

use App\Models\Currency;
use App\Models\ExchangeCurrencyPair;
use App\Models\ExchangeRate;

describe(ExchangeCurrencyPair::class, function (): void {
    it('belongs to base and quote currencies', function (): void {
        $base = Currency::query()->firstOrCreate(['code' => 'USD'], ['precision' => 2]);
        $quote = Currency::query()->firstOrCreate(['code' => 'VES'], ['precision' => 2]);

        $pair = ExchangeCurrencyPair::factory()->create([
            'base_currency_code' => $base->code,
            'quote_currency_code' => $quote->code,
        ]);

        expect($pair->baseCurrency)->toBeInstanceOf(Currency::class);
        expect($pair->baseCurrency->is($base))->toBeTrue();

        expect($pair->quoteCurrency)->toBeInstanceOf(Currency::class);
        expect($pair->quoteCurrency->is($quote))->toBeTrue();
    });

    it('has many exchange rates', function (): void {
        $pair = ExchangeCurrencyPair::factory()->create();

        ExchangeRate::factory()->create([
            'exchange_currency_pair_id' => $pair->id,
        ]);

        expect($pair->exchangeRates()->count())->toBe(1);
    });
});
