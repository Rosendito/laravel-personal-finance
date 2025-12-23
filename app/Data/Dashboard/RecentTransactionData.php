<?php

declare(strict_types=1);

namespace App\Data\Dashboard;

use Carbon\CarbonInterface;
use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

final class RecentTransactionData extends Data
{
    public function __construct(
        #[Required, Numeric]
        public int|float|string $transactionId,

        #[Required, Date]
        public CarbonInterface $effectiveAt,

        #[Required, StringType]
        public string $description,

        #[Nullable, StringType]
        public ?string $categoryName,

        #[Nullable, StringType]
        public ?string $budgetPeriodLabel,

        #[Required, Numeric]
        public int|float|string $expenseAmount,

        #[Required, Numeric]
        public int|float|string $incomeAmount,
    ) {}
}
