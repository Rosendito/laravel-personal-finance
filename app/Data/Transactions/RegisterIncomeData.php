<?php

declare(strict_types=1);

namespace App\Data\Transactions;

use Carbon\CarbonInterface;
use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

final class RegisterIncomeData extends Data
{
    public function __construct(
        #[Required, StringType]
        public string $description,

        #[Required, Date]
        public CarbonInterface $effective_at,

        #[Required, IntegerType]
        public int $account_id,

        #[Required, Numeric]
        public int|float|string $amount,

        #[Nullable, Date]
        public ?CarbonInterface $posted_at = null,

        #[Nullable, IntegerType]
        public ?int $category_id = null,

        #[Nullable, StringType]
        public ?string $memo = null,

        #[Nullable, StringType]
        public ?string $reference = null,

        #[Nullable, StringType]
        public ?string $source = null,

        #[Nullable, StringType]
        public ?string $idempotency_key = null,
    ) {}
}
