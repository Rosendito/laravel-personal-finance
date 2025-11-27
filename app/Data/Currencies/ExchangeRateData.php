<?php

declare(strict_types=1);

namespace App\Data\Currencies;

use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

final class ExchangeRateData extends Data
{
    /**
     * @param  array<float>  $prices
     */
    public function __construct(
        #[Required, Numeric]
        public float $averagePrice,

        #[Required, ArrayType]
        public array $prices,
    ) {}
}
