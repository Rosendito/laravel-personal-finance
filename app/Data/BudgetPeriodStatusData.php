<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Size;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class BudgetPeriodStatusData extends Data
{
    public function __construct(
        #[Required, IntegerType]
        public int $budget_id,

        #[Required, StringType]
        public string $budget_name,

        #[Required, StringType, Size(7)]
        public string $period,

        #[Required, StringType, Size(3)]
        public string $currency_code,

        #[Required, Numeric]
        public int|float|string $budgeted,

        #[Required, Numeric]
        public int|float|string $spent,

        #[Required, Numeric]
        public int|float|string $remaining,
    ) {}
}
