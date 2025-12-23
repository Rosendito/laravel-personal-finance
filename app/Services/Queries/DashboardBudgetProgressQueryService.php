<?php

declare(strict_types=1);

namespace App\Services\Queries;

use App\Data\Dashboard\BudgetProgressData;
use App\Enums\LedgerAccountType;
use App\Models\Budget;
use App\Models\BudgetPeriod;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class DashboardBudgetProgressQueryService
{
    /**
     * @return Collection<int, BudgetProgressData>
     */
    public function progress(User $user, CarbonInterface $startAt, CarbonInterface $endAt): Collection
    {
        $budgets = Budget::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->with('periods')
            ->get();

        return $budgets
            ->map(function (Budget $budget) use ($startAt, $endAt) {
                $period = $this->intersectingPeriod($budget, $startAt, $endAt);

                if ($period === null) {
                    return null;
                }

                $spent = $this->spentInRange($budget->id, $startAt, $endAt);

                $remaining = bcsub((string) $period->amount, $spent, 6);
                $usagePercent = $this->percentage($spent, (string) $period->amount);

                $periodLabel = sprintf('%s → %s', $period->start_at->toDateString(), $period->end_at->toDateString());

                return new BudgetProgressData(
                    budgetId: $budget->id,
                    budgetName: $budget->name,
                    periodLabel: $periodLabel,
                    amount: (string) $period->amount,
                    spent: $spent,
                    remaining: $remaining,
                    usagePercent: $usagePercent,
                );
            })
            ->filter()
            ->values();
    }

    private function intersectingPeriod(Budget $budget, CarbonInterface $startAt, CarbonInterface $endAt): ?BudgetPeriod
    {
        return $budget->periods
            ->sortByDesc('start_at')
            ->first(function (BudgetPeriod $period) use ($startAt, $endAt): bool {
                return $period->start_at <= $endAt && $period->end_at > $startAt;
            });
    }

    private function spentInRange(int $budgetId, CarbonInterface $startAt, CarbonInterface $endAt): string
    {
        $total = DB::query()
            ->from('ledger_entries as e')
            ->selectRaw('SUM(CASE WHEN COALESCE(e.amount_base, e.amount) > 0 THEN COALESCE(e.amount_base, e.amount) ELSE 0 END) as total')
            ->join('ledger_accounts as a', 'a.id', '=', 'e.account_id')
            ->join('ledger_transactions as t', 't.id', '=', 'e.transaction_id')
            ->join('categories as c', 'c.id', '=', 't.category_id')
            ->where('c.budget_id', $budgetId)
            ->where('a.type', LedgerAccountType::EXPENSE->value)
            ->whereBetween('t.effective_at', [
                $startAt->toDateString(),
                $endAt->toDateString(),
            ])
            ->value('total');

        return (string) ($total ?? '0');
    }

    private function percentage(string $numerator, string $denominator): string
    {
        if (bccomp($denominator, '0', 6) === 0) {
            return '0';
        }

        $ratio = bcdiv($numerator, $denominator, 4);

        return bcmul($ratio, '100', 2);
    }
}
