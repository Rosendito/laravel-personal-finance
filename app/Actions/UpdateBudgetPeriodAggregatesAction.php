<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\CachedAggregateKey;
use App\Models\BudgetPeriod;
use App\Services\Queries\BudgetPeriodSpentQueryService;

final class UpdateBudgetPeriodAggregatesAction
{
    public function __construct(
        private readonly BudgetPeriodSpentQueryService $spentQuery,
    ) {}

    public function execute(BudgetPeriod $period): void
    {
        $spent = $this->spentQuery->total($period);

        $period->upsertCachedAggregate(
            CachedAggregateKey::Spent,
            [
                'value_decimal' => $spent,
                'value_int' => null,
                'value_json' => null,
            ],
        );

        // Refresh the aggregates relationship if it was loaded to ensure the cached value is up to date
        if ($period->relationLoaded('aggregates')) {
            $period->load('aggregates');
        }
    }
}
