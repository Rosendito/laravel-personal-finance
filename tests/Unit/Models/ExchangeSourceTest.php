<?php

declare(strict_types=1);

use App\Models\ExchangeRate;
use App\Models\ExchangeSource;

describe(ExchangeSource::class, function (): void {
    it('has many exchange rates', function (): void {
        $source = ExchangeSource::factory()->create();

        ExchangeRate::factory()->create([
            'exchange_source_id' => $source->id,
        ]);

        expect($source->exchangeRates()->count())->toBe(1);
    });
});
