<?php

declare(strict_types=1);

namespace App\Actions\Currencies;

use App\Data\Currencies\ExchangeRateData;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Symfony\Component\DomCrawler\Crawler;

final class FetchBcvRateAction
{
    private const string CACHE_KEY = 'bcv_rate';

    private const int CACHE_TTL = 3600;

    private const string BCV_URL = 'https://www.bcv.org.ve/';

    private const string RATE_SELECTOR = '#dolar strong';

    public function execute(bool $force = false): ExchangeRateData
    {
        if ($force) {
            return $this->fetchRate();
        }

        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, $this->fetchRate(...));
    }

    private function fetchRate(): ExchangeRateData
    {
        $html = $this->fetchHtml();
        $rate = $this->extractRate($html);

        return $this->buildExchangeRateData($rate);
    }

    private function fetchHtml(): string
    {
        $response = Http::timeout(10)
            ->withOptions([
                'verify' => false,
            ])
            ->get(self::BCV_URL);

        if (! $response->successful()) {
            throw new RuntimeException("HTTP request failed with status: {$response->status()}");
        }

        return $response->body();
    }

    private function extractRate(string $html): float
    {
        $crawler = new Crawler($html);
        $node = $crawler->filter(self::RATE_SELECTOR);

        throw_if($node->count() === 0, RuntimeException::class, 'No BCV rate found on the page.');

        $rateString = mb_trim($node->text());

        return $this->parseRateString($rateString);
    }

    private function parseRateString(string $rateString): float
    {
        // Support Venezuelan format: "374,23" or "1.234,56"
        // Replace dots (thousands separator) with empty, then comma (decimal) with dot
        $normalized = str_replace(['.', ','], ['', '.'], $rateString);

        throw_unless(is_numeric($normalized), RuntimeException::class, "Could not parse BCV rate: {$rateString}");

        return (float) $normalized;
    }

    private function buildExchangeRateData(float $rate): ExchangeRateData
    {
        return new ExchangeRateData(
            averagePrice: $rate,
            prices: [$rate],
            maxPrice: $rate,
            minPrice: $rate,
            count: 1,
        );
    }
}
