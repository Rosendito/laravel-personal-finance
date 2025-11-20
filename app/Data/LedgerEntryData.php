<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Size;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

final class LedgerEntryData extends Data
{
    public function __construct(
        #[Required, IntegerType]
        public int $account_id,

        #[Required, Numeric]
        public int|float|string $amount,

        #[Nullable, StringType, Size(3)]
        public ?string $currency_code = null,

        #[Nullable, StringType]
        public ?string $memo = null,
    ) {}
}
