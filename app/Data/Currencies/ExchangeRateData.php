<?php

declare(strict_types=1);

namespace App\Data\Currencies;

use Livewire\Wireable;
use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Concerns\WireableData;
use Spatie\LaravelData\Data;

final class ExchangeRateData extends Data implements Wireable
{
    use WireableData;

    /**
     * @param  array<float>  $prices
     */
    public function __construct(
        #[Required, Numeric]
        public float $averagePrice,

        #[Required, ArrayType]
        public array $prices,

        #[Required, Numeric]
        public float $maxPrice,

        #[Required, Numeric]
        public float $minPrice,

        #[Required, Numeric]
        public int $count,
    ) {}
}
