<?php

declare(strict_types=1);

namespace App\Data\Dashboard;

use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

final class DashboardSnapshotData extends Data
{
    public function __construct(
        #[Required, Numeric]
        public int|float|string $liquidity,

        #[Required, Numeric]
        public int|float|string $loanReceivable,

        #[Required, Numeric]
        public int|float|string $liabilitiesOwed,
    ) {}
}
