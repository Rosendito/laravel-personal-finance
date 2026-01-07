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
use App\Services\ExchangeRates\Fetchers\BcvRateFetcher;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Tests\Mocks\ExchangeRates\FakeEmptyFetcher;
use Tests\Mocks\ExchangeRates\FakeUsdVesFetcher;

describe(SyncExchangeRatesAction::class, function (): void {
    $ensureCurrencies = function (string ...$codes): void {
        foreach ($codes as $code) {
            Currency::query()->updateOrCreate(['code' => $code], ['precision' => 2]);
        }
    };

    $upsertPair = function (string $baseCurrencyCode, string $quoteCurrencyCode): ExchangeCurrencyPair {
        return ExchangeCurrencyPair::query()->updateOrCreate([
            'base_currency_code' => $baseCurrencyCode,
            'quote_currency_code' => $quoteCurrencyCode,
        ]);
    };

    $upsertBcvSource = function (): ExchangeSource {
        return ExchangeSource::query()->updateOrCreate(
            ['key' => ExchangeSourceKey::BCV->value],
            [
                'name' => 'Banco Central de Venezuela',
                'type' => 'official',
                'metadata' => [],
            ],
        );
    };

    $attachPairsToSource = function (ExchangeSource $source, ExchangeCurrencyPair ...$pairs): void {
        foreach ($pairs as $pair) {
            $pair->exchangeSources()->syncWithoutDetaching([$source->id]);
        }
    };

    $fakeBcvFixtureHttpResponse = function (): void {
        $html = file_get_contents(base_path('tests/Fixtures/Html/venezuela-banco-central-rates.html'));

        expect($html)->toBeString()->not->toBeEmpty();

        Http::fake([
            'https://www.bcv.org.ve/' => Http::response($html, 200),
        ]);
    };

    /**
     * @return array<string, ExchangeRate>
     */
    $ratesByPairKey = function (ExchangeSource $source): array {
        return ExchangeRate::query()
            ->with('exchangeCurrencyPair')
            ->where('exchange_source_id', $source->id)
            ->get()
            ->keyBy(fn (ExchangeRate $rate): string => "{$rate->exchangeCurrencyPair->base_currency_code}/{$rate->exchangeCurrencyPair->quote_currency_code}")
            ->all();
    };

    $expectPersistedRateForPair = function (ExchangeRate $rate, string $expectedRate, string $expectedEffectiveAt): void {
        expect((string) $rate->rate)->toBe($expectedRate)
            ->and($rate->effective_at->toDateTimeString())->toBe($expectedEffectiveAt)
            ->and($rate->retrieved_at?->toDateTimeString())->toBe(now()->toDateTimeString())
            ->and($rate->is_estimated)->toBeFalse()
            ->and($rate->meta)->toMatchArray(['url' => 'https://www.bcv.org.ve/', 'strategy' => 'scraper']);
    };

    it('persists fetched exchange rates', function () use ($ensureCurrencies, $upsertPair, $upsertBcvSource, $attachPairsToSource): void {
        $ensureCurrencies('USD', 'VES');

        $pair = $upsertPair('USD', 'VES');

        $source = $upsertBcvSource();

        config()->set('finance.exchange_rates.fetchers', [
            ExchangeSourceKey::BCV->value => FakeUsdVesFetcher::class,
        ]);

        $attachPairsToSource($source, $pair);

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

    it('throws when requesting a pair not supported by the source', function () use ($ensureCurrencies, $upsertPair, $upsertBcvSource): void {
        $ensureCurrencies('USDT', 'VES');

        $unsupportedPair = $upsertPair('USDT', 'VES');

        $source = $upsertBcvSource();

        config()->set('finance.exchange_rates.fetchers', [
            ExchangeSourceKey::BCV->value => FakeUsdVesFetcher::class,
        ]);

        expect(fn (): Collection => resolve(SyncExchangeRatesAction::class)->execute(
            $source,
            RequestedPairsData::forPairs($unsupportedPair),
        ))->toThrow(ExchangeRatesException::class);
    });

    it('throws when fetcher does not return all requested pairs', function () use ($ensureCurrencies, $upsertPair, $upsertBcvSource, $attachPairsToSource): void {
        $ensureCurrencies('USD', 'VES');

        $pair = $upsertPair('USD', 'VES');

        $source = $upsertBcvSource();

        config()->set('finance.exchange_rates.fetchers', [
            ExchangeSourceKey::BCV->value => FakeEmptyFetcher::class,
        ]);

        $attachPairsToSource($source, $pair);

        expect(fn (): Collection => resolve(SyncExchangeRatesAction::class)->execute(
            $source,
            RequestedPairsData::forPairs($pair),
        ))->toThrow(ExchangeRatesException::class);
    });

    it('persists BCV USD/VES and EUR/VES rates using the real fetcher', function () use (
        $attachPairsToSource,
        $ensureCurrencies,
        $expectPersistedRateForPair,
        $fakeBcvFixtureHttpResponse,
        $ratesByPairKey,
        $upsertBcvSource,
        $upsertPair,
    ): void {
        $fakeBcvFixtureHttpResponse();

        $ensureCurrencies('USD', 'EUR', 'VES');

        $usdVes = $upsertPair('USD', 'VES');
        $eurVes = $upsertPair('EUR', 'VES');

        $source = $upsertBcvSource();

        config()->set('finance.exchange_rates.fetchers', [
            ExchangeSourceKey::BCV->value => BcvRateFetcher::class,
        ]);

        $attachPairsToSource($source, $usdVes, $eurVes);

        resolve(SyncExchangeRatesAction::class)->execute(
            $source,
            RequestedPairsData::forPairs($usdVes, $eurVes),
        );

        expect(ExchangeRate::query()->count())->toBe(2);

        $rates = $ratesByPairKey($source);

        expect(array_keys($rates))->toMatchArray(['USD/VES', 'EUR/VES']);

        $usd = $rates['USD/VES'];
        $eur = $rates['EUR/VES'];

        $expectedEffectiveAt = now()->setDate(2026, 1, 7)->startOfDay()->toDateTimeString();

        $expectPersistedRateForPair($usd, '311.881400000000000000', $expectedEffectiveAt);
        $expectPersistedRateForPair($eur, '364.838861720000000000', $expectedEffectiveAt);
    });
});
