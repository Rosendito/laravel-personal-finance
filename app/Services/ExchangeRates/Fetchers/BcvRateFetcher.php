<?php

declare(strict_types=1);

namespace App\Services\ExchangeRates\Fetchers;

use App\Contracts\ExchangeRates\ExchangeRateFetcher;
use App\Data\ExchangeRates\FetchedExchangeRateData;
use App\Data\ExchangeRates\RequestedPairsData;
use App\Models\ExchangeSource;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Symfony\Component\DomCrawler\Crawler;

final class BcvRateFetcher implements ExchangeRateFetcher
{
    private const string BCV_URL = 'https://www.bcv.org.ve/';

    /**
     * @return Collection<int, FetchedExchangeRateData>
     */
    public function fetch(ExchangeSource $source, ?RequestedPairsData $requestedPairs = null): Collection
    {
        $requestedPairs ??= RequestedPairsData::forAllPairs();

        $html = $this->fetchHtml();

        $retrievedAt = now();
        $effectiveAt = $this->extractEffectiveAt($html);

        $rates = $this->extractRates($html, $effectiveAt, $retrievedAt);

        if ($requestedPairs->isAll()) {
            return $rates;
        }

        $requestedKeys = $requestedPairs->pairKeys();

        return $rates
            ->filter(fn (FetchedExchangeRateData $rate): bool => in_array($rate->pairKey(), $requestedKeys, true))
            ->values();
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

        return (string) $response->body();
    }

    private function extractEffectiveAt(string $html): CarbonInterface
    {
        $crawler = new Crawler($html);
        $node = $crawler->filter('.pull-right.dinpro.center .date-display-single');

        throw_if($node->count() === 0, RuntimeException::class, 'No BCV effective date found on the page.');

        $content = $node->attr('content');

        throw_unless(is_string($content) && $content !== '', RuntimeException::class, 'No BCV effective date content found on the page.');

        $date = mb_substr($content, 0, 10);
        $effectiveAt = Date::createFromFormat('Y-m-d', $date);

        throw_unless($effectiveAt instanceof CarbonInterface, RuntimeException::class, "Could not parse BCV effective date: {$content}");

        return $effectiveAt->startOfDay();
    }

    /**
     * @return Collection<int, FetchedExchangeRateData>
     */
    private function extractRates(string $html, CarbonInterface $effectiveAt, CarbonInterface $retrievedAt): Collection
    {
        $crawler = new Crawler($html);

        $ratesByBase = [
            'USD' => $this->extractRate($crawler, '#dolar strong'),
            'EUR' => $this->extractRate($crawler, '#euro strong'),
        ];

        return collect([
            $this->buildRate('USD', 'VES', $ratesByBase['USD'], $effectiveAt, $retrievedAt),
            $this->buildRate('EUR', 'VES', $ratesByBase['EUR'], $effectiveAt, $retrievedAt),
        ]);
    }

    private function extractRate(Crawler $crawler, string $selector): string
    {
        $node = $crawler->filter($selector);

        throw_if($node->count() === 0, RuntimeException::class, "No BCV rate found for selector: {$selector}");

        $rateString = mb_trim($node->text());

        return $this->parseRateString($rateString);
    }

    private function parseRateString(string $rateString): string
    {
        // Support Venezuelan format: "374,23" or "1.234,56"
        // Replace dots (thousands separator) with empty, then comma (decimal) with dot
        $normalized = str_replace(['.', ','], ['', '.'], $rateString);

        throw_unless(is_numeric($normalized), RuntimeException::class, "Could not parse BCV rate: {$rateString}");

        return $normalized;
    }

    private function buildRate(
        string $baseCurrencyCode,
        string $quoteCurrencyCode,
        string $rate,
        CarbonInterface $effectiveAt,
        CarbonInterface $retrievedAt,
    ): FetchedExchangeRateData {
        return new FetchedExchangeRateData(
            baseCurrencyCode: $baseCurrencyCode,
            quoteCurrencyCode: $quoteCurrencyCode,
            rate: $rate,
            effectiveAt: $effectiveAt,
            retrievedAt: $retrievedAt,
            isEstimated: false,
            metadata: [
                'url' => self::BCV_URL,
                'strategy' => 'scraper',
            ],
        );
    }
}
