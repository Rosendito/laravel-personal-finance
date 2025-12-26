<?php

declare(strict_types=1);

namespace App\Services\Queries;

use App\Models\BudgetPeriod;
use Carbon\CarbonInterface;

final class BudgetPeriodForDateQueryService
{
    public function forBudget(int $budgetId, CarbonInterface $date): ?BudgetPeriod
    {
        $anchor = $date->toDateString();

        return BudgetPeriod::query()
            ->where('budget_id', $budgetId)
            ->whereDate('start_at', '<=', $anchor)
            ->whereDate('end_at', '>', $anchor)
            ->orderByDesc('start_at')
            ->first();
    }
}
