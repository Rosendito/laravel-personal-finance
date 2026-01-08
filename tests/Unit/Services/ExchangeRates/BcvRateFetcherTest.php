<?php

declare(strict_types=1);

use App\Data\ExchangeRates\RequestedPairsData;
use App\Enums\ExchangeSourceKey;
use App\Models\Currency;
use App\Models\ExchangeCurrencyPair;
use App\Models\ExchangeSource;
use App\Services\ExchangeRates\Fetchers\BcvRateFetcher;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Http;

describe(BcvRateFetcher::class, function (): void {
    it('fetches USD/VES and EUR/VES from the BCV homepage', function (): void {
        $html = file_get_contents(base_path('tests/Fixtures/Html/venezuela-banco-central-rates.html'));

        expect($html)->toBeString()->not->toBeEmpty();

        Http::fake([
            'https://www.bcv.org.ve/' => Http::response($html, 200),
        ]);

        $source = ExchangeSource::query()->updateOrCreate(
            ['key' => ExchangeSourceKey::BCV->value],
            [
                'name' => 'Banco Central de Venezuela',
                'type' => 'official',
                'metadata' => [],
            ],
        );

        $rates = new BcvRateFetcher()->fetch($source);

        expect($rates)->toHaveCount(2);

        $usd = $rates->firstWhere('baseCurrencyCode', 'USD');
        $eur = $rates->firstWhere('baseCurrencyCode', 'EUR');

        expect($usd)->not->toBeNull()
            ->and($usd->quoteCurrencyCode)->toBe('VES')
            ->and($usd->rate)->toBe('311.88140000')
            ->and($usd->effectiveAt->toDateTimeString())->toBe(Date::parse('2026-01-07 00:00:00')->toDateTimeString())
            ->and($usd->retrievedAt->toDateTimeString())->toBe(now()->toDateTimeString())
            ->and($usd->metadata)->toMatchArray(['url' => 'https://www.bcv.org.ve/', 'strategy' => 'scraper']);

        expect($eur)->not->toBeNull()
            ->and($eur->quoteCurrencyCode)->toBe('VES')
            ->and($eur->rate)->toBe('364.83886172')
            ->and($eur->effectiveAt->toDateTimeString())->toBe(Date::parse('2026-01-07 00:00:00')->toDateTimeString())
            ->and($eur->retrievedAt->toDateTimeString())->toBe(now()->toDateTimeString())
            ->and($eur->metadata)->toMatchArray(['url' => 'https://www.bcv.org.ve/', 'strategy' => 'scraper']);
    });

    it('can be limited to only the requested pairs', function (): void {
        $html = file_get_contents(base_path('tests/Fixtures/Html/venezuela-banco-central-rates.html'));

        expect($html)->toBeString()->not->toBeEmpty();

        Http::fake([
            'https://www.bcv.org.ve/' => Http::response($html, 200),
        ]);

        Currency::query()->updateOrCreate(['code' => 'USD'], ['precision' => 2]);
        Currency::query()->updateOrCreate(['code' => 'VES'], ['precision' => 2]);

        $usdVes = ExchangeCurrencyPair::query()->updateOrCreate([
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

        $rates = new BcvRateFetcher()->fetch($source, RequestedPairsData::forPairs($usdVes));

        expect($rates)->toHaveCount(1)
            ->and($rates->first()?->pairKey())->toBe('USD/VES');
    });
});
