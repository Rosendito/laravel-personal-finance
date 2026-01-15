<?php

declare(strict_types=1);

use App\Models\ExchangeCurrencyPair;
use App\Models\ExchangeRate;
use App\Models\ExchangeSource;
use Carbon\CarbonInterface;

describe(ExchangeRate::class, function (): void {
    it('belongs to an exchange currency pair and an exchange source', function (): void {
        $rate = ExchangeRate::factory()->create();

        expect($rate->exchangeCurrencyPair)->toBeInstanceOf(ExchangeCurrencyPair::class);
        expect($rate->exchangeSource)->toBeInstanceOf(ExchangeSource::class);
    });

    it('casts attributes correctly', function (): void {
        $rate = ExchangeRate::factory()->create([
            'is_estimated' => true,
            'meta' => ['notes' => 'test'],
        ]);

        expect($rate->rate)->toBeString();
        expect($rate->effective_at)->toBeInstanceOf(CarbonInterface::class);
        expect($rate->is_estimated)->toBeTrue();
        expect($rate->meta)->toBeArray()->toHaveKey('notes', 'test');
    });
});
