<?php

declare(strict_types=1);

namespace App\Services\Queries;

use App\Data\BudgetPeriodStatusData;
use App\Enums\CategoryType;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class BudgetStatusQueryService
{
    /**
     * @return Collection<int, BudgetPeriodStatusData>
     */
    public function periodStatus(User $user, string $period): Collection
    {
        $transactionMonthExpression = $this->monthExtractExpression('t.effective_at');

        $rows = DB::query()
            ->from('budgets as b')
            ->select([
                'b.id as budget_id',
                'b.name as budget_name',
                'bp.period',
                'bp.currency_code',
            ])
            ->selectRaw('bp.amount as budgeted')
            ->selectRaw('COALESCE(SUM(CASE WHEN c.type = ? THEN e.amount ELSE 0 END), 0) as spent', [
                CategoryType::Expense->value,
            ])
            ->selectRaw('(bp.amount - COALESCE(SUM(CASE WHEN c.type = ? THEN e.amount ELSE 0 END), 0)) as remaining', [
                CategoryType::Expense->value,
            ])
            ->join('budget_periods as bp', function (JoinClause $join) use ($period): void {
                $join->on('bp.budget_id', '=', 'b.id')
                    ->where('bp.period', '=', $period);
            })
            ->leftJoin('ledger_transactions as t', function (JoinClause $join) use ($transactionMonthExpression): void {
                $join->on('t.budget_id', '=', 'b.id')
                    ->whereRaw("{$transactionMonthExpression} = bp.period");
            })
            ->leftJoin('ledger_entries as e', 'e.transaction_id', '=', 't.id')
            ->leftJoin('categories as c', 'c.id', '=', 'e.category_id')
            ->where('b.user_id', $user->id)
            ->groupBy([
                'b.id',
                'b.name',
                'bp.period',
                'bp.amount',
                'bp.currency_code',
            ])
            ->orderBy('b.name')
            ->get();

        return $rows->map(
            static fn ($row): BudgetPeriodStatusData => new BudgetPeriodStatusData(
                budget_id: (int) $row->budget_id,
                budget_name: $row->budget_name,
                period: $row->period,
                currency_code: $row->currency_code,
                budgeted: bcadd((string) $row->budgeted, '0', 6),
                spent: bcadd((string) ($row->spent ?? '0'), '0', 6),
                remaining: bcadd((string) ($row->remaining ?? '0'), '0', 6),
            )
        );
    }

    public function addSpentAmountSubselect(Builder $query, string $period): Builder
    {
        $transactionMonthExpression = $this->monthExtractExpression('t.effective_at');

        return $query->addSelect([
            'spent_amount' => DB::query()
                ->from('ledger_entries as e')
                ->selectRaw('COALESCE(SUM(e.amount), 0)')
                ->join('ledger_transactions as t', 't.id', '=', 'e.transaction_id')
                ->join('categories as c', 'c.id', '=', 'e.category_id')
                ->whereColumn('t.budget_id', 'budgets.id')
                ->whereRaw("{$transactionMonthExpression} = ?", [$period])
                ->where('c.type', CategoryType::Expense->value),
        ]);
    }

    private function monthExtractExpression(string $qualifiedColumn): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => "strftime('%Y-%m', {$qualifiedColumn})",
            'pgsql' => "TO_CHAR({$qualifiedColumn}, 'YYYY-MM')",
            default => "DATE_FORMAT({$qualifiedColumn}, '%Y-%m')",
        };
    }
}
