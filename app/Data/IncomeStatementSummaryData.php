<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

final class IncomeStatementSummaryData extends Data
{
    public function __construct(
        #[Required, Numeric]
        public int|float|string $total_income,

        #[Required, Numeric]
        public int|float|string $total_expense,

        #[Required, Numeric]
        public int|float|string $net_income,
    ) {}
}
