<?php

declare(strict_types=1);

use App\Data\ExchangeRates\RequestedPairsData;
use App\Enums\ExchangeSourceKey;
use App\Models\Currency;
use App\Models\ExchangeCurrencyPair;
use App\Models\ExchangeSource;
use App\Services\ExchangeRates\Fetchers\BinanceRateFetcher;
use Illuminate\Support\Facades\Http;

describe(BinanceRateFetcher::class, function (): void {
    $readFixtureJson = function (): string {
        $json = file_get_contents(base_path('tests/Fixtures/Http/binance-p2p-usdt-ves.json'));

        expect($json)->toBeString()->not->toBeEmpty();

        return $json;
    };

    $fakeBinanceFixtureHttpResponse = function (string $json): void {
        Http::fake([
            'https://p2p.binance.com/bapi/c2c/v2/friendly/c2c/adv/search' => Http::response($json, 200),
        ]);
    };

    $ensureCurrencies = function (string ...$codes): void {
        foreach ($codes as $code) {
            Currency::query()->updateOrCreate(['code' => $code], ['precision' => 2]);
        }
    };

    $upsertPair = (fn (string $baseCurrencyCode, string $quoteCurrencyCode): ExchangeCurrencyPair => ExchangeCurrencyPair::query()->updateOrCreate([
        'base_currency_code' => $baseCurrencyCode,
        'quote_currency_code' => $quoteCurrencyCode,
    ]));

    $upsertBinanceSource = (fn (): ExchangeSource => ExchangeSource::query()->updateOrCreate(
        ['key' => ExchangeSourceKey::BINANCE_P2P->value],
        [
            'name' => 'Binance P2P',
            'type' => 'api',
            'metadata' => [],
        ],
    ));

    $attachPairsToSource = function (ExchangeSource $source, ExchangeCurrencyPair ...$pairs): void {
        foreach ($pairs as $pair) {
            $pair->exchangeSources()->syncWithoutDetaching([$source->id]);
        }
    };

    it('fetches USDT/VES from the Binance P2P API fixture', function () use (
        $attachPairsToSource,
        $ensureCurrencies,
        $fakeBinanceFixtureHttpResponse,
        $readFixtureJson,
        $upsertBinanceSource,
        $upsertPair,
    ): void {
        $json = $readFixtureJson();

        $fakeBinanceFixtureHttpResponse($json);

        $ensureCurrencies('USDT', 'VES');

        $usdtVes = $upsertPair('USDT', 'VES');

        $source = $upsertBinanceSource();

        $attachPairsToSource($source, $usdtVes);

        $rates = resolve(BinanceRateFetcher::class)->fetch($source, RequestedPairsData::forPairs($usdtVes));

        expect($rates)->toHaveCount(1);

        $rate = $rates->first();

        expect($rate)->not->toBeNull()
            ->and($rate->pairKey())->toBe('USDT/VES')
            ->and($rate->rate)->not->toBeEmpty()
            ->and(is_numeric($rate->rate))->toBeTrue()
            ->and($rate->effectiveAt->toDateTimeString())->toBe(now()->toDateTimeString())
            ->and($rate->retrievedAt->toDateTimeString())->toBe(now()->toDateTimeString())
            ->and($rate->metadata)->toMatchArray([
                'strategy' => 'best_price',
                'url' => 'https://p2p.binance.com',
                'endpoint' => '/bapi/c2c/v2/friendly/c2c/adv/search',
                'asset' => 'USDT',
                'fiat' => 'VES',
            ]);
    });

    it('can fetch all supported pairs when no RequestedPairsData is provided', function () use (
        $attachPairsToSource,
        $ensureCurrencies,
        $fakeBinanceFixtureHttpResponse,
        $readFixtureJson,
        $upsertBinanceSource,
        $upsertPair,
    ): void {
        $json = $readFixtureJson();

        $fakeBinanceFixtureHttpResponse($json);

        $ensureCurrencies('USDT', 'VES');

        $usdtVes = $upsertPair('USDT', 'VES');

        $source = $upsertBinanceSource();

        $attachPairsToSource($source, $usdtVes);

        $rates = resolve(BinanceRateFetcher::class)->fetch($source);

        expect($rates)->toHaveCount(1)
            ->and($rates->first()?->pairKey())->toBe('USDT/VES');
    });
});
