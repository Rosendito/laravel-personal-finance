<?php

declare(strict_types=1);

use App\Console\Commands\SyncExchangeRates;
use App\Enums\ExchangeSourceKey;
use App\Jobs\ExchangeRates\SyncExchangeRatesJob;
use App\Models\Currency;
use App\Models\ExchangeCurrencyPair;
use App\Models\ExchangeSource;
use Illuminate\Support\Facades\Queue;

use function Pest\Laravel\artisan;

describe(SyncExchangeRates::class, function (): void {
    $ensureCurrencies = function (string ...$codes): void {
        foreach ($codes as $code) {
            Currency::query()->updateOrCreate(['code' => $code], ['precision' => 2]);
        }
    };

    $upsertPair = (fn (string $baseCurrencyCode, string $quoteCurrencyCode): ExchangeCurrencyPair => ExchangeCurrencyPair::query()->updateOrCreate([
        'base_currency_code' => $baseCurrencyCode,
        'quote_currency_code' => $quoteCurrencyCode,
    ]));

    $upsertSource = (fn (ExchangeSourceKey $key, string $name, string $type): ExchangeSource => ExchangeSource::query()->updateOrCreate(
        ['key' => $key->value],
        [
            'name' => $name,
            'type' => $type,
            'metadata' => [],
        ],
    ));

    $attachPairsToSource = function (ExchangeSource $source, ExchangeCurrencyPair ...$pairs): void {
        foreach ($pairs as $pair) {
            $pair->exchangeSources()->syncWithoutDetaching([$source->id]);
        }
    };

    it('dispatches a job by inferring the source from a pair key', function () use ($attachPairsToSource, $ensureCurrencies, $upsertPair, $upsertSource): void {
        Queue::fake();

        $ensureCurrencies('USDT', 'VES');

        $pair = $upsertPair('USDT', 'VES');
        $source = $upsertSource(ExchangeSourceKey::BINANCE_P2P, 'Binance P2P', 'api');

        $attachPairsToSource($source, $pair);

        artisan('exchange-rates:sync', [
            '--pair' => ['USDT/VES'],
        ])->assertExitCode(0);

        Queue::assertPushed(SyncExchangeRatesJob::class, function (SyncExchangeRatesJob $job) use ($pair, $source): bool {
            sort($job->exchangeCurrencyPairIds);

            return $job->exchangeSourceId === $source->id
                && $job->exchangeCurrencyPairIds === [$pair->id];
        });
    });

    it('dispatches one job per source when pairs include explicit source prefixes', function () use ($attachPairsToSource, $ensureCurrencies, $upsertPair, $upsertSource): void {
        Queue::fake();

        $ensureCurrencies('USDT', 'USD', 'VES');

        $usdtVes = $upsertPair('USDT', 'VES');
        $usdVes = $upsertPair('USD', 'VES');

        $binance = $upsertSource(ExchangeSourceKey::BINANCE_P2P, 'Binance P2P', 'api');
        $bcv = $upsertSource(ExchangeSourceKey::BCV, 'Banco Central de Venezuela', 'official');

        $attachPairsToSource($binance, $usdtVes);
        $attachPairsToSource($bcv, $usdVes);

        artisan('exchange-rates:sync', [
            '--pair' => ['binance_p2p:USDT/VES', 'bcv:USD/VES'],
        ])->assertExitCode(0);

        Queue::assertPushed(SyncExchangeRatesJob::class, 2);

        Queue::assertPushed(SyncExchangeRatesJob::class, function (SyncExchangeRatesJob $job) use ($binance, $usdtVes): bool {
            sort($job->exchangeCurrencyPairIds);

            return $job->exchangeSourceId === $binance->id
                && $job->exchangeCurrencyPairIds === [$usdtVes->id];
        });

        Queue::assertPushed(SyncExchangeRatesJob::class, function (SyncExchangeRatesJob $job) use ($bcv, $usdVes): bool {
            sort($job->exchangeCurrencyPairIds);

            return $job->exchangeSourceId === $bcv->id
                && $job->exchangeCurrencyPairIds === [$usdVes->id];
        });
    });

    it('dispatches an all-pairs job when only a source is provided', function () use ($upsertSource): void {
        Queue::fake();

        $source = $upsertSource(ExchangeSourceKey::BINANCE_P2P, 'Binance P2P', 'api');

        artisan('exchange-rates:sync', [
            '--source' => [$source->key],
        ])->assertExitCode(0);

        Queue::assertPushed(SyncExchangeRatesJob::class, fn (SyncExchangeRatesJob $job): bool => $job->exchangeSourceId === $source->id
            && $job->exchangeCurrencyPairIds === []);
    });

    it('dispatches a job when a pair id is provided', function () use ($attachPairsToSource, $ensureCurrencies, $upsertPair, $upsertSource): void {
        Queue::fake();

        $ensureCurrencies('USDT', 'VES');

        $pair = $upsertPair('USDT', 'VES');
        $source = $upsertSource(ExchangeSourceKey::BINANCE_P2P, 'Binance P2P', 'api');

        $attachPairsToSource($source, $pair);

        artisan('exchange-rates:sync', [
            '--pair-id' => [(string) $pair->id],
        ])->assertExitCode(0);

        Queue::assertPushed(SyncExchangeRatesJob::class, function (SyncExchangeRatesJob $job) use ($pair, $source): bool {
            sort($job->exchangeCurrencyPairIds);

            return $job->exchangeSourceId === $source->id
                && $job->exchangeCurrencyPairIds === [$pair->id];
        });
    });

    it('does not dispatch any jobs when using dry-run', function () use ($attachPairsToSource, $ensureCurrencies, $upsertPair, $upsertSource): void {
        Queue::fake();

        $ensureCurrencies('USDT', 'VES');

        $pair = $upsertPair('USDT', 'VES');
        $source = $upsertSource(ExchangeSourceKey::BINANCE_P2P, 'Binance P2P', 'api');

        $attachPairsToSource($source, $pair);

        artisan('exchange-rates:sync', [
            '--pair' => ['USDT/VES'],
            '--dry-run' => true,
        ])->assertExitCode(0);

        Queue::assertNothingPushed();
    });
});
