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
        string $tradeType = 'BUY',
    ): ExchangeRateData {
        $payload = [
            'page' => 1,
            'rows' => $rows,
            'payTypes' => [],
            'asset' => $sellAsset,
            'tradeType' => $tradeType,
            'fiat' => $buyFiat,
            'transAmount' => (string) $transAmount,
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

        if (! is_array($data) || ! ($data['success'] ?? false)) {
            throw new RuntimeException('Unexpected response structure or success flag is false.');
        }

        if (! isset($data['data']) || ! is_array($data['data']) || count($data['data']) === 0) {
            throw new RuntimeException('No ads data returned from API.');
        }

        $ads = array_slice($data['data'], 0, $rows);

        $prices = [];

        foreach ($ads as $ad) {
            $priceString = $ad['adv']['price'] ?? null;

            if ($priceString === null) {
                continue;
            }

            $prices[] = (float) $priceString;
        }

        if (count($prices) === 0) {
            throw new RuntimeException("No prices found in the first {$rows} ads.");
        }

        $average = array_sum($prices) / count($prices);

        return new ExchangeRateData(
            averagePrice: $average,
            prices: $prices,
        );
    }
}
