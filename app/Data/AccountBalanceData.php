<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Attributes\Validation\BooleanType;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Size;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class AccountBalanceData extends Data
{
    public function __construct(
        #[Required, IntegerType]
        public int $account_id,

        #[Required, StringType]
        public string $name,

        #[Required, StringType, Size(3)]
        public string $currency_code,

        #[Required, Numeric]
        public int|float|string $balance,

        #[Required, BooleanType]
        public bool $is_fundamental,

        #[Nullable, StringType]
        public ?string $subtype = null,
    ) {}
}
