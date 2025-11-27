<?php

declare(strict_types=1);

namespace App\Actions\Currencies;

use App\Data\Currencies\ExchangeRateData;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Symfony\Component\DomCrawler\Crawler;

final class FetchBcvRateAction
{
    public function execute(): ExchangeRateData
    {
        $response = Http::timeout(10)
            ->withOptions([
                'verify' => false,
            ])
            ->get('https://www.bcv.org.ve/');

        if (! $response->successful()) {
            throw new RuntimeException("HTTP request failed with status: {$response->status()}");
        }

        $html = $response->body();
        $crawler = new Crawler($html);

        $node = $crawler->filter('#dolar strong');

        if ($node->count() === 0) {
            throw new RuntimeException('No BCV rate found on the page.');
        }

        $rateString = mb_trim($node->text());
        // Support Venezuelan format: "374,23" or "1.234,56"
        // Replace dots (thousands separator) with empty, then comma (decimal) with dot
        $normalized = str_replace(['.', ','], ['', '.'], $rateString);

        if (! is_numeric($normalized)) {
            throw new RuntimeException("Could not parse BCV rate: {$rateString}");
        }

        $rate = (float) $normalized;

        return new ExchangeRateData(
            averagePrice: $rate,
            prices: [$rate],
            maxPrice: $rate,
            minPrice: $rate,
            count: 1,
        );
    }
}
