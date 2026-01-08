<?php

declare(strict_types=1);

namespace App\Services\ExchangeRates\Fetchers;

use App\Contracts\ExchangeRates\ExchangeRateFetcher;
use App\Data\ExchangeRates\FetchedExchangeRateData;
use App\Data\ExchangeRates\RequestedPairsData;
use App\Models\ExchangeCurrencyPair;
use App\Models\ExchangeSource;
use App\Services\BinanceP2P\BinanceP2PClient;
use App\Services\ExchangeRates\RateCalculatorResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final readonly class BinanceRateFetcher implements ExchangeRateFetcher
{
    public function __construct(
        private BinanceP2PClient $client,
        private RateCalculatorResolver $rateCalculatorResolver,
    ) {}

    /**
     * @return Collection<int, FetchedExchangeRateData>
     */
    public function fetch(ExchangeSource $source, ?RequestedPairsData $requestedPairs = null): Collection
    {
        $requestedPairs ??= RequestedPairsData::forAllPairs();

        $pairs = $requestedPairs->isAll()
            ? $this->supportedPairsForSource($source)
            : (($requestedPairs->pairs ?? collect())->values());

        $retrievedAt = now();

        return $pairs
            ->map(function (ExchangeCurrencyPair $pair) use ($source, $retrievedAt): FetchedExchangeRateData {
                $tradeType = $this->tradeType($source, $pair);
                $transAmount = $this->transAmount($source, $pair);
                $rows = $this->rows($source, $pair);
                $payTypes = $this->payTypes($source, $pair);

                $ads = $this->client->searchAds(
                    asset: $pair->base_currency_code,
                    fiat: $pair->quote_currency_code,
                    tradeType: $tradeType,
                    transAmount: $transAmount,
                    rows: $rows,
                    page: 1,
                    payTypes: $payTypes,
                );

                $quotes = $this->normalizeAds($ads);

                $calculator = $this->rateCalculatorResolver->resolve($source, $pair);
                $result = $calculator->calculate($source, $pair, $quotes);

                /** @var array<string, mixed> $calculatorMetadata */
                $calculatorMetadata = (array) ($result['metadata'] ?? []);

                $rate = (string) ($result['rate'] ?? '');

                return new FetchedExchangeRateData(
                    baseCurrencyCode: $pair->base_currency_code,
                    quoteCurrencyCode: $pair->quote_currency_code,
                    rate: $rate,
                    effectiveAt: $retrievedAt,
                    retrievedAt: $retrievedAt,
                    isEstimated: false,
                    metadata: array_merge($calculatorMetadata, [
                        'url' => 'https://p2p.binance.com',
                        'endpoint' => '/bapi/c2c/v2/friendly/c2c/adv/search',
                        'asset' => $pair->base_currency_code,
                        'fiat' => $pair->quote_currency_code,
                        'requested_trade_type' => $tradeType,
                        'requested_trans_amount' => $transAmount,
                        'requested_rows' => $rows,
                        'requested_pay_types' => $payTypes,
                    ]),
                );
            })
            ->filter()
            ->values();
    }

    /**
     * @return Collection<int, ExchangeCurrencyPair>
     */
    private function supportedPairsForSource(ExchangeSource $source): Collection
    {
        return ExchangeCurrencyPair::query()
            ->whereHas('exchangeSources', fn (Builder $query): Builder => $query->whereKey($source->id))
            ->get();
    }

    private function tradeType(ExchangeSource $source, ExchangeCurrencyPair $pair): string
    {
        $pairKey = "{$pair->base_currency_code}/{$pair->quote_currency_code}";

        $metadataTradeType = data_get($source->metadata, "binance_p2p.trade_type.by_pair.{$pairKey}", data_get($source->metadata, 'binance_p2p.trade_type.default'));

        $tradeType = $metadataTradeType ?? 'SELL';

        return mb_strtoupper((string) $tradeType);
    }

    private function transAmount(ExchangeSource $source, ExchangeCurrencyPair $pair): int|float|string
    {
        $pairKey = "{$pair->base_currency_code}/{$pair->quote_currency_code}";

        return data_get($source->metadata, "binance_p2p.trans_amount.by_pair.{$pairKey}", data_get($source->metadata, 'binance_p2p.trans_amount.default', 10000));
    }

    private function rows(ExchangeSource $source, ExchangeCurrencyPair $pair): int
    {
        $pairKey = "{$pair->base_currency_code}/{$pair->quote_currency_code}";

        $rows = data_get($source->metadata, "binance_p2p.rows.by_pair.{$pairKey}", data_get($source->metadata, 'binance_p2p.rows.default', 10));

        return (int) $rows;
    }

    /**
     * @return array<int, string>
     */
    private function payTypes(ExchangeSource $source, ExchangeCurrencyPair $pair): array
    {
        $pairKey = "{$pair->base_currency_code}/{$pair->quote_currency_code}";

        $payTypes = data_get($source->metadata, "binance_p2p.pay_types.by_pair.{$pairKey}", data_get($source->metadata, 'binance_p2p.pay_types.default', []));

        return is_array($payTypes) ? array_values(array_filter($payTypes, fn (mixed $v): bool => is_string($v) && $v !== '')) : [];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $ads
     * @return Collection<int, array<string, mixed>>
     */
    private function normalizeAds(Collection $ads): Collection
    {
        return $ads
            ->map(function (array $row): array {
                /** @var array<string, mixed> $adv */
                $adv = (array) ($row['adv'] ?? []);

                /** @var array<string, mixed> $advertiser */
                $advertiser = (array) ($row['advertiser'] ?? []);

                /** @var array<int, array<string, mixed>> $tradeMethods */
                $tradeMethods = is_array($adv['tradeMethods'] ?? null) ? $adv['tradeMethods'] : [];

                return [
                    'price' => data_get($adv, 'price'),
                    'trade_type' => data_get($adv, 'tradeType'),
                    'asset' => data_get($adv, 'asset'),
                    'fiat' => data_get($adv, 'fiatUnit'),
                    'min_single_trans_amount' => data_get($adv, 'minSingleTransAmount'),
                    'max_single_trans_amount' => data_get($adv, 'maxSingleTransAmount'),
                    'surplus_amount' => data_get($adv, 'surplusAmount'),
                    'tradable_quantity' => data_get($adv, 'tradableQuantity'),
                    'pay_time_limit' => data_get($adv, 'payTimeLimit'),
                    'advertiser_nickname' => data_get($advertiser, 'nickName'),
                    'month_finish_rate' => data_get($advertiser, 'monthFinishRate'),
                    'positive_rate' => data_get($advertiser, 'positiveRate'),
                    'trade_methods' => collect($tradeMethods)
                        ->map(fn (array $m): array => [
                            'pay_type' => data_get($m, 'payType'),
                            'identifier' => data_get($m, 'identifier'),
                            'name' => data_get($m, 'tradeMethodName'),
                        ])
                        ->values()
                        ->all(),
                ];
            })
            ->values();
    }
}
