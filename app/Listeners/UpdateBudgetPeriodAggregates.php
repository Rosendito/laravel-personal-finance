<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Actions\UpdateBudgetPeriodAggregatesAction;
use App\Events\LedgerTransactionCreated;
use App\Models\BudgetPeriod;

final class UpdateBudgetPeriodAggregates
{
    public function __construct(
        private readonly UpdateBudgetPeriodAggregatesAction $updateAggregatesAction,
    ) {}

    public function handle(LedgerTransactionCreated $event): void
    {
        $transaction = $event->transaction->loadMissing('budgetPeriod');

        /** @var BudgetPeriod|null $period */
        $period = $transaction->budgetPeriod;

        if ($period === null) {
            return;
        }

        $this->updateAggregatesAction->execute($period);
    }
}
