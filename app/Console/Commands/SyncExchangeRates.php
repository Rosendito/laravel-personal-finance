<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\ExchangeRates\SyncExchangeRatesJob;
use App\Models\ExchangeCurrencyPair;
use App\Models\ExchangeSource;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

final class SyncExchangeRates extends Command
{
    protected $signature = 'exchange-rates:sync
                            {--source=* : Exchange source key (repeatable). Example: --source=binance_p2p}
                            {--pair=* : Pair as BASE/QUOTE or sourceKey:BASE/QUOTE (repeatable). Example: --pair=USDT/VES or --pair=bcv:USD/VES}
                            {--pair-id=* : Pair id or sourceKey:id (repeatable). Example: --pair-id=12 or --pair-id=binance_p2p:12}
                            {--dry-run : Do not dispatch jobs, only print what would be dispatched}';

    protected $description = 'Dispatch exchange rate sync jobs for one or more sources and optional pairs.';

    public function handle(): int
    {
        $runId = (string) Str::uuid();
        $requestedAt = now()->toIso8601String();
        $input = $this->parseInputOptions();

        try {
            $this->ensureAtLeastOneFilterProvided($input);

            $requests = $this->buildRequests(
                sourceKeys: $input['sourceKeys'],
                rawPairs: $input['rawPairs'],
                rawPairIds: $input['rawPairIds'],
            );

            $jobs = $this->dispatchRequests(
                requests: $requests,
                requestedSourceKeys: $input['sourceKeys'],
                isDryRun: $input['isDryRun'],
            );

            $this->logSuccess(runId: $runId, requestedAt: $requestedAt, input: $input, jobs: $jobs);

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());
            $this->logFailure(runId: $runId, requestedAt: $requestedAt, input: $input, exception: $exception);

            return self::FAILURE;
        }
    }

    /**
     * @return array{sourceKeys: array<int, string>, rawPairs: array<int, string>, rawPairIds: array<int, string>, isDryRun: bool}
     */
    private function parseInputOptions(): array
    {
        /** @var array<int, string> $sourceKeys */
        $sourceKeys = $this->parseStringListOption('source');

        /** @var array<int, string> $rawPairs */
        $rawPairs = $this->parseStringListOption('pair');

        /** @var array<int, string> $rawPairIds */
        $rawPairIds = $this->parseStringListOption('pair-id');

        return [
            'sourceKeys' => $sourceKeys,
            'rawPairs' => $rawPairs,
            'rawPairIds' => $rawPairIds,
            'isDryRun' => (bool) $this->option('dry-run'),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function parseStringListOption(string $optionName): array
    {
        return collect((array) $this->option($optionName))
            ->filter(fn (mixed $value): bool => is_string($value) && mb_trim($value) !== '')
            ->map(fn (string $value): string => mb_trim($value))
            ->values()
            ->all();
    }

    /**
     * @param  array{sourceKeys: array<int, string>, rawPairs: array<int, string>, rawPairIds: array<int, string>, isDryRun: bool}  $input
     */
    private function ensureAtLeastOneFilterProvided(array $input): void
    {
        throw_if($input['sourceKeys'] === [] && $input['rawPairs'] === [] && $input['rawPairIds'] === [], RuntimeException::class, 'Provide at least one of: --source, --pair, or --pair-id.');
    }

    /**
     * @param  array<int, string>  $sourceKeys
     * @param  array<int, string>  $rawPairs
     * @param  array<int, string>  $rawPairIds
     * @return array<string, array{source: ExchangeSource, pairIds: array<int, int>, hasSpecificPairs: bool}>
     */
    private function buildRequests(array $sourceKeys, array $rawPairs, array $rawPairIds): array
    {
        $requests = $this->seedRequestsFromSources($sourceKeys);

        $this->applyPairInputs(
            requests: $requests,
            rawPairs: $rawPairs,
            requestedSourceKeys: $sourceKeys,
        );

        $this->applyPairIdInputs(
            requests: $requests,
            rawPairIds: $rawPairIds,
            requestedSourceKeys: $sourceKeys,
        );

        throw_if($requests === [], RuntimeException::class, 'No jobs to dispatch.');

        return $requests;
    }

    /**
     * @param  array<int, string>  $sourceKeys
     * @return array<string, array{source: ExchangeSource, pairIds: array<int, int>, hasSpecificPairs: bool}>
     */
    private function seedRequestsFromSources(array $sourceKeys): array
    {
        /** @var array<string, array{source: ExchangeSource, pairIds: array<int, int>, hasSpecificPairs: bool}> $requests */
        $requests = [];

        foreach ($sourceKeys as $sourceKey) {
            $source = $this->requireSourceByKey($sourceKey);

            $requests[$sourceKey] = [
                'source' => $source,
                'pairIds' => [],
                'hasSpecificPairs' => false,
            ];
        }

        return $requests;
    }

    /**
     * @param  array<string, array{source: ExchangeSource, pairIds: array<int, int>, hasSpecificPairs: bool}>  $requests
     * @param  array<int, string>  $rawPairs
     * @param  array<int, string>  $requestedSourceKeys
     */
    private function applyPairInputs(array &$requests, array $rawPairs, array $requestedSourceKeys): void
    {
        foreach ($rawPairs as $rawPair) {
            [$explicitSourceKey, $pairKeyRaw] = $this->splitSourcePrefix($rawPair);

            $pairKey = $this->normalizePairKey($pairKeyRaw);
            throw_if($pairKey === null, RuntimeException::class, "Invalid pair [{$rawPair}]. Expected BASE/QUOTE or sourceKey:BASE/QUOTE.");

            $pair = $this->findPairByKey($pairKey);
            throw_unless($pair instanceof ExchangeCurrencyPair, RuntimeException::class, "Exchange currency pair [{$pairKey}] was not found in the database.");

            $sourceKey = $this->resolveSourceKeyForPair(
                explicitSourceKey: $explicitSourceKey,
                explicitSourceKeys: $requestedSourceKeys,
                pair: $pair,
            );

            throw_if($sourceKey === null, RuntimeException::class, "Unable to infer source for pair [{$pairKey}]. Provide --source=... or prefix the pair like sourceKey:{$pairKey}.");

            $source = $requests[$sourceKey]['source'] ?? $this->requireSourceByKey($sourceKey);

            throw_unless($pair->isSupportedBy($source), RuntimeException::class, "Pair [{$pairKey}] is not supported by source [{$sourceKey}].");

            $requests[$sourceKey] ??= [
                'source' => $source,
                'pairIds' => [],
                'hasSpecificPairs' => false,
            ];

            $requests[$sourceKey]['pairIds'][] = $pair->id;
            $requests[$sourceKey]['hasSpecificPairs'] = true;
        }
    }

    /**
     * @param  array<string, array{source: ExchangeSource, pairIds: array<int, int>, hasSpecificPairs: bool}>  $requests
     * @param  array<int, string>  $rawPairIds
     * @param  array<int, string>  $requestedSourceKeys
     */
    private function applyPairIdInputs(array &$requests, array $rawPairIds, array $requestedSourceKeys): void
    {
        foreach ($rawPairIds as $rawPairId) {
            [$explicitSourceKey, $idRaw] = $this->splitSourcePrefix($rawPairId);

            throw_unless(ctype_digit($idRaw), RuntimeException::class, "Invalid pair id [{$rawPairId}]. Expected an integer id or sourceKey:id.");

            $pairId = (int) $idRaw;
            $pair = ExchangeCurrencyPair::query()->find($pairId);

            throw_unless($pair instanceof ExchangeCurrencyPair, RuntimeException::class, "Exchange currency pair id [{$pairId}] was not found in the database.");

            $sourceKey = $this->resolveSourceKeyForPair(
                explicitSourceKey: $explicitSourceKey,
                explicitSourceKeys: $requestedSourceKeys,
                pair: $pair,
            );

            throw_if($sourceKey === null, RuntimeException::class, "Unable to infer source for pair id [{$pairId}]. Provide --source=... or prefix the pair id like sourceKey:{$pairId}.");

            $source = $requests[$sourceKey]['source'] ?? $this->requireSourceByKey($sourceKey);

            $pairKey = "{$pair->base_currency_code}/{$pair->quote_currency_code}";
            throw_unless($pair->isSupportedBy($source), RuntimeException::class, "Pair [{$pairKey}] is not supported by source [{$sourceKey}].");

            $requests[$sourceKey] ??= [
                'source' => $source,
                'pairIds' => [],
                'hasSpecificPairs' => false,
            ];

            $requests[$sourceKey]['pairIds'][] = $pair->id;
            $requests[$sourceKey]['hasSpecificPairs'] = true;
        }
    }

    /**
     * @param  array<string, array{source: ExchangeSource, pairIds: array<int, int>, hasSpecificPairs: bool}>  $requests
     * @param  array<int, string>  $requestedSourceKeys
     * @return array<int, array{source_key: string, source_id: int, sync_all_pairs: bool, pair_ids: array<int, int>}>
     */
    private function dispatchRequests(array $requests, array $requestedSourceKeys, bool $isDryRun): array
    {
        /** @var array<int, array{source_key: string, source_id: int, sync_all_pairs: bool, pair_ids: array<int, int>}> $jobs */
        $jobs = [];

        foreach ($requests as $sourceKey => $request) {
            $source = $request['source'];
            $pairIds = array_values(array_unique($request['pairIds']));

            $willSyncAll = in_array($sourceKey, $requestedSourceKeys, true) && $request['hasSpecificPairs'] === false;

            if ($isDryRun) {
                $pairsLabel = $willSyncAll ? '[all pairs]' : ('['.implode(', ', $pairIds).']');
                $this->line("Dry run: would dispatch SyncExchangeRatesJob for source [{$source->key}] with pair ids {$pairsLabel}.");
            } elseif ($willSyncAll) {
                dispatch(new SyncExchangeRatesJob($source->id));
                $this->info("Dispatched sync job for source [{$source->key}] (all pairs).");
            } else {
                dispatch(new SyncExchangeRatesJob($source->id, $pairIds));
                $this->info("Dispatched sync job for source [{$source->key}] (".count($pairIds).' pair(s)).');
            }

            $jobs[] = [
                'source_key' => (string) $source->key,
                'source_id' => $source->id,
                'sync_all_pairs' => $willSyncAll,
                'pair_ids' => $pairIds,
            ];
        }

        return $jobs;
    }

    /**
     * @param  array{sourceKeys: array<int, string>, rawPairs: array<int, string>, rawPairIds: array<int, string>, isDryRun: bool}  $input
     * @param  array<int, array{source_key: string, source_id: int, sync_all_pairs: bool, pair_ids: array<int, int>}>  $jobs
     */
    private function logSuccess(string $runId, string $requestedAt, array $input, array $jobs): void
    {
        Log::info('exchange-rates:sync succeeded', [
            'run_id' => $runId,
            'requested_at' => $requestedAt,
            'dry_run' => $input['isDryRun'],
            'source_keys' => $input['sourceKeys'],
            'pair_inputs' => $input['rawPairs'],
            'pair_id_inputs' => $input['rawPairIds'],
            'jobs' => $jobs,
        ]);
    }

    /**
     * @param  array{sourceKeys: array<int, string>, rawPairs: array<int, string>, rawPairIds: array<int, string>, isDryRun: bool}  $input
     */
    private function logFailure(string $runId, string $requestedAt, array $input, Throwable $exception): void
    {
        Log::error('exchange-rates:sync failed', [
            'run_id' => $runId,
            'requested_at' => $requestedAt,
            'message' => $exception->getMessage(),
            'dry_run' => $input['isDryRun'],
            'source_keys' => $input['sourceKeys'],
            'pair_inputs' => $input['rawPairs'],
            'pair_id_inputs' => $input['rawPairIds'],
            'exception' => $exception,
        ]);
    }

    private function requireSourceByKey(string $sourceKey): ExchangeSource
    {
        $source = $this->findSourceByKey($sourceKey);
        throw_unless($source instanceof ExchangeSource, RuntimeException::class, "Exchange source [{$sourceKey}] was not found.");

        return $source;
    }

    private function findSourceByKey(string $sourceKey): ?ExchangeSource
    {
        return ExchangeSource::query()
            ->where('key', $sourceKey)
            ->first();
    }

    private function findPairByKey(string $pairKey): ?ExchangeCurrencyPair
    {
        [$base, $quote] = explode('/', $pairKey, 2);

        return ExchangeCurrencyPair::query()
            ->where('base_currency_code', $base)
            ->where('quote_currency_code', $quote)
            ->first();
    }

    private function normalizePairKey(string $pairKey): ?string
    {
        $pairKey = mb_strtoupper(mb_trim($pairKey));

        if (! preg_match('/^[A-Z0-9]{2,10}\/[A-Z0-9]{2,10}$/', $pairKey)) {
            return null;
        }

        return $pairKey;
    }

    /**
     * @return array{0: string|null, 1: string}
     */
    private function splitSourcePrefix(string $value): array
    {
        if (! str_contains($value, ':')) {
            return [null, $value];
        }

        [$sourceKey, $rest] = explode(':', $value, 2);
        $sourceKey = mb_trim($sourceKey);
        $rest = mb_trim($rest);

        if ($sourceKey === '' || $rest === '') {
            return [null, $value];
        }

        return [$sourceKey, $rest];
    }

    private function resolveSourceKeyForPair(
        ?string $explicitSourceKey,
        array $explicitSourceKeys,
        ExchangeCurrencyPair $pair,
    ): ?string {
        if (is_string($explicitSourceKey) && $explicitSourceKey !== '') {
            return $explicitSourceKey;
        }

        if (count($explicitSourceKeys) === 1) {
            return $explicitSourceKeys[0];
        }

        $supportedSourceKeys = $pair->exchangeSources()
            ->pluck('key')
            ->filter(fn (mixed $key): bool => is_string($key) && $key !== '')
            ->values()
            ->all();

        if (count($supportedSourceKeys) === 1) {
            return $supportedSourceKeys[0];
        }

        return null;
    }
}
