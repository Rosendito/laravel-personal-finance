<?php

declare(strict_types=1);

namespace App\Data\Dashboard;

use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

final class CashflowSeriesData extends Data
{
    /**
     * @param  array<int, string>  $labels
     * @param  array<int, string|int|float>  $expenses
     * @param  array<int, string|int|float>  $incomes
     */
    public function __construct(
        #[Required, ArrayType, StringType]
        public array $labels,

        #[Required, ArrayType]
        public array $expenses,

        #[Required, ArrayType]
        public array $incomes,
    ) {}
}
