<?php

declare(strict_types=1);

namespace App\Data\ExchangeRates;

use Carbon\CarbonInterface;
use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\BooleanType;
use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Size;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

final class FetchedExchangeRateData extends Data
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        #[Required, StringType, Size(3)]
        public string $baseCurrencyCode,

        #[Required, StringType, Size(3)]
        public string $quoteCurrencyCode,

        #[Required, StringType]
        public string $rate,

        #[Required, Date]
        public CarbonInterface $effectiveAt,

        #[Required, Date]
        public CarbonInterface $retrievedAt,

        #[Required, BooleanType]
        public bool $isEstimated = false,

        #[Required, ArrayType]
        public array $metadata = [],
    ) {}

    public function pairKey(): string
    {
        return "{$this->baseCurrencyCode}/{$this->quoteCurrencyCode}";
    }
}
