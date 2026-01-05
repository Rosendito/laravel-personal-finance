<?php

declare(strict_types=1);

namespace App\Actions\Currencies;

use App\Data\Currencies\ExchangeRateData;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class FetchBinanceRateAction
{
    public function execute(
        int|float|string $transAmount,
        int $rows,
        string $sellAsset,
        string $buyFiat,
        string $tradeType = 'SELL',
        array $payTypes = [], // ['Banesco']
    ): ExchangeRateData {
        $payload = [
            'fiat' => $buyFiat,
            'page' => 1,
            'rows' => $rows,
            'tradeType' => $tradeType,
            'asset' => $sellAsset,
            'countries' => [],
            'proMerchantAds' => false,
            'shieldMerchantAds' => false,
            'filterType' => 'tradable',
            'periods' => [],
            'additionalKycVerifyFilter' => 0,
            'publisherType' => 'merchant',
            'payTypes' => $payTypes,
            'classifies' => [
                'mass',
                'profession',
                'fiat_trade',
            ],
            'tradedWith' => false,
            'followed' => false,
            'transAmount' => $transAmount,
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post(
            'https://p2p.binance.com/bapi/c2c/v2/friendly/c2c/adv/search',
            $payload
        );

        if (! $response->successful()) {
            throw new RuntimeException("HTTP request failed with status: {$response->status()}");
        }

        $data = $response->json();

        throw_if(! is_array($data) || ! ($data['success'] ?? false), RuntimeException::class, 'Unexpected response structure or success flag is false.');

        throw_if(! isset($data['data']) || ! is_array($data['data']) || count($data['data']) === 0, RuntimeException::class, 'No ads data returned from API.');

        $ads = array_slice($data['data'], 0, $rows);

        $prices = [];

        foreach ($ads as $ad) {
            $priceString = $ad['adv']['price'] ?? null;

            if ($priceString === null) {
                continue;
            }

            $prices[] = (float) $priceString;
        }

        throw_if(count($prices) === 0, RuntimeException::class, "No prices found in the first {$rows} ads.");

        $average = array_sum($prices) / count($prices);
        $maxPrice = max($prices);
        $minPrice = min($prices);
        $count = count($prices);

        return new ExchangeRateData(
            averagePrice: $average,
            prices: $prices,
            maxPrice: $maxPrice,
            minPrice: $minPrice,
            count: $count,
        );
    }
}
