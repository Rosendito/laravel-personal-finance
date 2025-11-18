<?php

declare(strict_types=1);

namespace App\Data;

use Carbon\CarbonInterface;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\Validation\ArrayType;
use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\IntegerType;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

final class LedgerTransactionData extends Data
{
    /**
     * @param  DataCollection<int, LedgerEntryData>  $entries
     */
    public function __construct(
        #[Required, IntegerType]
        public int $account_id,

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

        #[Required, ArrayType, Min(2)]
        #[DataCollectionOf(LedgerEntryData::class)]
        public DataCollection $entries = new DataCollection(LedgerEntryData::class, []),
    ) {}
}
