<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Actions\UpdateBudgetPeriodAggregatesAction;
use App\Events\LedgerTransactionCreated;
use App\Events\LedgerTransactionUpdated;
use App\Models\BudgetPeriod;

final readonly class UpdateBudgetPeriodAggregates
{
    public function __construct(
        private UpdateBudgetPeriodAggregatesAction $updateAggregatesAction,
    ) {}

    public function handle(LedgerTransactionCreated|LedgerTransactionUpdated $event): void
    {
        $periodIds = [
            $event->transaction->budget_period_id,
        ];

        if ($event instanceof LedgerTransactionUpdated) {
            /** @var LedgerTransactionUpdated $updatedEvent */
            $updatedEvent = $event;

            $periodIds[] = $updatedEvent->previousBudgetPeriodId;
        }

        $periodIds = array_values(array_unique(array_filter($periodIds, static fn (?int $id): bool => $id !== null)));

        if ($periodIds === []) {
            return;
        }

        $periods = BudgetPeriod::query()
            ->whereIn('id', $periodIds)
            ->get();

        foreach ($periods as $period) {
            $this->updateAggregatesAction->execute($period);
        }
    }
}
