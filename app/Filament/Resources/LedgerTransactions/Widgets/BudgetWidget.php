<?php

declare(strict_types=1);

namespace App\Filament\Resources\LedgerTransactions\Widgets;

use App\Helpers\MoneyFormatter;
use App\Models\Budget;
use App\Services\Queries\BudgetPeriodForDateQueryService;
use Carbon\CarbonImmutable;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

final class BudgetWidget extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected int|string|array $columnSpan = 'full';

    protected int|array|null $columns = 4;

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

        $anchorDate = $this->anchorDateFromPageFilters();
        $periodResolver = $anchorDate instanceof CarbonImmutable ? resolve(BudgetPeriodForDateQueryService::class) : null;

        return Budget::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->unless(
                $anchorDate instanceof CarbonImmutable,
                static fn (Builder $query): Builder => $query->with('currentPeriod'),
            )
            ->get()
            ->map(function (Budget $budget) use ($currency, $anchorDate, $periodResolver): ?BudgetStat {
                $period = $anchorDate instanceof CarbonImmutable
                    ? $periodResolver?->forBudget($budget->id, $anchorDate)
                    : $budget->currentPeriod;

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

    private function anchorDateFromPageFilters(): ?CarbonImmutable
    {
        $start = $this->pageFilters['start_at'] ?? null;

        if (is_string($start) && $start !== '') {
            return CarbonImmutable::parse($start)->startOfDay();
        }

        $end = $this->pageFilters['end_at'] ?? null;

        if (is_string($end) && $end !== '') {
            return CarbonImmutable::parse($end)->subDay()->startOfDay();
        }

        return null;
    }
}
