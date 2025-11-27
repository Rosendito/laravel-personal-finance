<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\Budget;
use App\Models\BudgetPeriod;
use App\Events\LedgerTransactionCreated;
use App\Events\LedgerTransactionUpdated;
use App\Actions\UpdateBudgetPeriodAggregatesAction;

final class UpdateBudgetPeriodAggregates
{
    public function __construct(
        private readonly UpdateBudgetPeriodAggregatesAction $updateAggregatesAction,
    ) {}

    public function handle(LedgerTransactionCreated|LedgerTransactionUpdated $event): void
    {
        // Update all budget periods from an user
        $budgets = Budget::query()->whereBelongsTo($event->transaction->user)
            ->with('currentPeriod')
            ->get();

        foreach ($budgets as $budget) {
            $this->updateAggregatesAction->execute($budget->currentPeriod);
        }
    }
}
