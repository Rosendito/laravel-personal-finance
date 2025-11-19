<?php

declare(strict_types=1);

namespace App\Filament\Resources\LedgerTransactions\Widgets;

use App\Helpers\MoneyFormatter;
use App\Models\Budget;
use Filament\Widgets\StatsOverviewWidget;
use Illuminate\Support\Facades\Auth;

final class BudgetWidget extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

    /**
     * @return array<int, BudgetStat>
     */
    protected function getStats(): array
    {
        $user = Auth::user();

        if ($user === null) {
            return [];
        }

        $currency = config('finance.currency.default');

        return Budget::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->with('currentPeriod')
            ->get()
            ->map(function (Budget $budget) use ($currency) {
                $period = $budget->currentPeriod;

                if ($period === null) {
                    return null;
                }

                return BudgetStat::fromBudget(
                    name: $budget->name,
                    spent: MoneyFormatter::format($period->spent_amount, $currency),
                    total: MoneyFormatter::format($period->amount, $currency),
                    remaining: MoneyFormatter::format($period->remaining_amount, $currency),
                    percentage: (float) $period->usage_percent,
                );
            })
            ->filter()
            ->values()
            ->all();
    }

    protected int|array|null $columns = 4;
}
