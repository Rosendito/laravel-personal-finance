<?php

declare(strict_types=1);

namespace App\Data\Transactions;

use Carbon\CarbonInterface;
use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

final class UpdateLedgerTransactionData extends Data
{
    public function __construct(
        #[Required, StringType]
        public string $description,

        #[Required, Date]
        public CarbonInterface $effective_at,

        #[Nullable, Date]
        public ?CarbonInterface $posted_at = null,

        #[Nullable, StringType]
        public ?string $reference = null,

        #[Nullable, IntegerType]
        public ?int $category_id = null,
    ) {}
}
