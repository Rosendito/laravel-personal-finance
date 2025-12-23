<?php

declare(strict_types=1);

namespace App\Data\Dashboard;

use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

final class CategoryTotalData extends Data
{
    public function __construct(
        public ?int $categoryId,

        #[Required, StringType]
        public string $name,

        #[Required, Numeric]
        public int|float|string $total,
    ) {}
}
