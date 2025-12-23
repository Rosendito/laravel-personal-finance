<?php

declare(strict_types=1);

namespace App\Data\Dashboard;

use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

final class BudgetProgressData extends Data
{
    public function __construct(
        #[Required, IntegerType]
        public int $budgetId,

        #[Required, StringType]
        public string $budgetName,

        #[Required, StringType]
        public string $periodLabel,

        #[Required, Numeric]
        public int|float|string $amount,

        #[Required, Numeric]
        public int|float|string $spent,

        #[Required, Numeric]
        public int|float|string $remaining,

        #[Required, Numeric]
        public int|float|string $usagePercent,
    ) {}
}
