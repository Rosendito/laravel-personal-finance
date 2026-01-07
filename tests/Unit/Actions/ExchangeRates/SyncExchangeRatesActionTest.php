<?php

declare(strict_types=1);

use App\Actions\ExchangeRates\SyncExchangeRatesAction;
use App\Data\ExchangeRates\RequestedPairsData;
use App\Enums\ExchangeSourceKey;
use App\Exceptions\ExchangeRatesException;
use App\Models\Currency;
use App\Models\ExchangeCurrencyPair;
use App\Models\ExchangeRate;
use App\Models\ExchangeSource;
use Illuminate\Support\Collection;
use Tests\Mocks\ExchangeRates\FakeEmptyFetcher;
use Tests\Mocks\ExchangeRates\FakeUsdVesFetcher;

describe(SyncExchangeRatesAction::class, function (): void {
    it('persists fetched exchange rates', function (): void {
        Currency::query()->updateOrCreate(['code' => 'USD'], ['precision' => 2]);
        Currency::query()->updateOrCreate(['code' => 'VES'], ['precision' => 2]);

        $pair = ExchangeCurrencyPair::query()->updateOrCreate([
            'base_currency_code' => 'USD',
            'quote_currency_code' => 'VES',
        ]);

        $source = ExchangeSource::query()->updateOrCreate(
            ['key' => ExchangeSourceKey::BCV->value],
            [
                'name' => 'Banco Central de Venezuela',
                'type' => 'official',
                'metadata' => [],
            ],
        );

        config()->set('finance.exchange_rates.fetchers', [
            ExchangeSourceKey::BCV->value => FakeUsdVesFetcher::class,
        ]);

        $pair->exchangeSources()->syncWithoutDetaching([$source->id]);

        resolve(SyncExchangeRatesAction::class)->execute($source);

        $rate = ExchangeRate::query()->first();

        expect($rate)->not->toBeNull()
            ->and($rate->exchange_currency_pair_id)->toBe($pair->id)
            ->and($rate->exchange_source_id)->toBe($source->id)
            ->and((string) $rate->rate)->toBe('36.500000000000000000')
            ->and($rate->retrieved_at?->toDateTimeString())->toBe(now()->toDateTimeString())
            ->and($rate->effective_at->toDateTimeString())->toBe(now()->toDateTimeString())
            ->and($rate->is_estimated)->toBeFalse()
            ->and($rate->meta)->toMatchArray(['strategy' => 'fixed']);
    });

    it('throws when requesting a pair not supported by the source', function (): void {
        Currency::query()->updateOrCreate(['code' => 'USDT'], ['precision' => 2]);
        Currency::query()->updateOrCreate(['code' => 'VES'], ['precision' => 2]);

        $unsupportedPair = ExchangeCurrencyPair::query()->updateOrCreate([
            'base_currency_code' => 'USDT',
            'quote_currency_code' => 'VES',
        ]);

        $source = ExchangeSource::query()->updateOrCreate(
            ['key' => ExchangeSourceKey::BCV->value],
            [
                'name' => 'Banco Central de Venezuela',
                'type' => 'official',
                'metadata' => [],
            ],
        );

        config()->set('finance.exchange_rates.fetchers', [
            ExchangeSourceKey::BCV->value => FakeUsdVesFetcher::class,
        ]);

        expect(fn (): Collection => resolve(SyncExchangeRatesAction::class)->execute(
            $source,
            RequestedPairsData::forPairs($unsupportedPair),
        ))->toThrow(ExchangeRatesException::class);
    });

    it('throws when fetcher does not return all requested pairs', function (): void {
        Currency::query()->updateOrCreate(['code' => 'USD'], ['precision' => 2]);
        Currency::query()->updateOrCreate(['code' => 'VES'], ['precision' => 2]);

        $pair = ExchangeCurrencyPair::query()->updateOrCreate([
            'base_currency_code' => 'USD',
            'quote_currency_code' => 'VES',
        ]);

        $source = ExchangeSource::query()->updateOrCreate(
            ['key' => ExchangeSourceKey::BCV->value],
            [
                'name' => 'Banco Central de Venezuela',
                'type' => 'official',
                'metadata' => [],
            ],
        );

        config()->set('finance.exchange_rates.fetchers', [
            ExchangeSourceKey::BCV->value => FakeEmptyFetcher::class,
        ]);

        $pair->exchangeSources()->syncWithoutDetaching([$source->id]);

        expect(fn (): Collection => resolve(SyncExchangeRatesAction::class)->execute(
            $source,
            RequestedPairsData::forPairs($pair),
        ))->toThrow(ExchangeRatesException::class);
    });
});
