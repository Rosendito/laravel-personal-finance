<?php

declare(strict_types=1);

namespace App\Services\BinanceP2P;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class BinanceP2PClient
{
    private const string BASE_URL = 'https://p2p.binance.com';

    private const string SEARCH_ADS_PATH = '/bapi/c2c/v2/friendly/c2c/adv/search';

    /**
     * @param  array<int, string>  $payTypes
     * @return Collection<int, array<string, mixed>>
     */
    public function searchAds(
        string $asset,
        string $fiat,
        string $tradeType,
        int|float|string $transAmount = 10000,
        int $rows = 10,
        int $page = 1,
        array $payTypes = [],
    ): Collection {
        $payload = [
            'fiat' => $fiat,
            'page' => $page,
            'rows' => $rows,
            'tradeType' => $tradeType,
            'asset' => $asset,
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

        $response = $this->request()
            ->post(self::BASE_URL.self::SEARCH_ADS_PATH, $payload);

        if (! $response->successful()) {
            throw new RuntimeException("Binance P2P request failed with status: {$response->status()}");
        }

        $json = $response->json();

        throw_unless(is_array($json), RuntimeException::class, 'Binance P2P returned a non-JSON or non-array payload.');

        $success = (bool) data_get($json, 'success', data_get($json, 'code') === '000000');

        if (! $success) {
            $code = (string) data_get($json, 'code', '');
            $message = (string) data_get($json, 'message', 'Unknown error');

            throw new RuntimeException("Binance P2P success flag is false. Code: [{$code}] Message: [{$message}]");
        }

        $data = data_get($json, 'data');

        throw_unless(is_array($data), RuntimeException::class, 'Binance P2P response did not include a valid [data] array.');

        /** @var Collection<int, array<string, mixed>> $collection */
        $collection = collect($data)->values();

        return $collection;
    }

    private function request(): PendingRequest
    {
        return Http::timeout(10)->withHeaders([
            'Content-Type' => 'application/json',
        ]);
    }
}
