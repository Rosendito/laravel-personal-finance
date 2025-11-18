<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\CachedAggregateKey;
use App\Events\LedgerTransactionCreated;
use App\Models\BudgetPeriod;
use App\Services\Queries\BudgetPeriodSpentQueryService;

final class UpdateBudgetPeriodAggregates
{
    public function __construct(
        private readonly BudgetPeriodSpentQueryService $spentQuery,
    ) {}

    public function handle(LedgerTransactionCreated $event): void
    {
        $transaction = $event->transaction->loadMissing('budgetPeriod');

        /** @var BudgetPeriod|null $period */
        $period = $transaction->budgetPeriod;

        if ($period === null) {
            return;
        }

        $spent = $this->spentQuery->total($period);

        $period->upsertCachedAggregate(
            CachedAggregateKey::Spent,
            [
                'value_decimal' => $spent,
                'value_int' => null,
                'value_json' => null,
            ],
        );
    }
}
