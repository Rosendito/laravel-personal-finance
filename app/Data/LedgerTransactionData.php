<?php

declare(strict_types=1);

namespace App\Data;

use Carbon\CarbonInterface;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Size;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

final class LedgerTransactionData extends Data
{
    /**
     * @param  DataCollection<int, LedgerEntryData>  $entries
     */
    public function __construct(
        #[Required, StringType]
        public string $description,

        #[Required, Date]
        public CarbonInterface $effective_at,

        #[Nullable, Date]
        public ?CarbonInterface $posted_at = null,

        #[Nullable, StringType]
        public ?string $reference = null,

        #[Nullable, StringType]
        public ?string $source = null,

        #[Nullable, StringType]
        public ?string $idempotency_key = null,

        #[Nullable, Numeric]
        public int|float|string|null $exchange_rate = null,

        #[Nullable, StringType, Size(3)]
        public ?string $currency_code = null,

        #[Required, ArrayType, Min(2)]
        #[DataCollectionOf(LedgerEntryData::class)]
        public DataCollection $entries = new DataCollection(LedgerEntryData::class, []),
    ) {}
}
