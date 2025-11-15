<?php

declare(strict_types=1);

namespace App\Services\Queries;

use App\Data\BudgetAllocationStatusData;
use App\Enums\CategoryType;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class BudgetStatusQueryService
{
    /**
     * @return Collection<int, BudgetAllocationStatusData>
     */
    public function periodStatus(User $user, string $period): Collection
    {
        $effectiveMonthExpression = $this->monthExtractExpression('t.effective_at');

        $rows = DB::query()
            ->from('budgets as b')
            ->select([
                'b.id as budget_id',
                'b.name as budget_name',
                'b.period',
                'ba.id as allocation_id',
                'c.id as category_id',
                'c.name as category_name',
                'ba.currency_code',
            ])
            ->selectRaw('ba.amount as budgeted')
            ->selectRaw('COALESCE(SUM(e.amount), 0) as spent')
            ->selectRaw('(ba.amount - COALESCE(SUM(e.amount), 0)) as remaining')
            ->join('budget_allocations as ba', 'ba.budget_id', '=', 'b.id')
            ->join('categories as c', 'c.id', '=', 'ba.category_id')
            ->leftJoin('ledger_transactions as t', function (JoinClause $join) use ($effectiveMonthExpression): void {
                $join->on('t.user_id', '=', 'b.user_id')
                    ->whereRaw("{$effectiveMonthExpression} = b.period");
            })
            ->leftJoin('ledger_entries as e', function (JoinClause $join): void {
                $join->on('e.transaction_id', '=', 't.id')
                    ->on('e.category_id', '=', 'c.id');
            })
            ->where('b.user_id', $user->id)
            ->where('b.period', $period)
            ->where('c.type', CategoryType::Expense->value)
            ->whereColumn('c.user_id', 'b.user_id')
            ->groupBy([
                'b.id',
                'b.name',
                'b.period',
                'ba.id',
                'c.id',
                'c.name',
                'ba.amount',
                'ba.currency_code',
            ])
            ->orderBy('c.name')
            ->get();

        return $rows->map(
            static fn ($row): BudgetAllocationStatusData => new BudgetAllocationStatusData(
                budget_id: (int) $row->budget_id,
                budget_name: $row->budget_name,
                period: $row->period,
                allocation_id: (int) $row->allocation_id,
                category_id: (int) $row->category_id,
                category_name: $row->category_name,
                currency_code: $row->currency_code,
                budgeted: bcadd((string) $row->budgeted, '0', 6),
                spent: bcadd((string) ($row->spent ?? '0'), '0', 6),
                remaining: bcadd((string) ($row->remaining ?? '0'), '0', 6),
            )
        );
    }

    public function addSpentAmountSubselect(Builder $query): Builder
    {
        $transactionMonthExpression = $this->monthExtractExpression('t.effective_at');

        return $query->addSelect([
            'spent_amount' => DB::query()
                ->from('ledger_entries as e')
                ->selectRaw('COALESCE(SUM(e.amount), 0)')
                ->join('ledger_transactions as t', 't.id', '=', 'e.transaction_id')
                ->whereColumn('t.user_id', 'budgets.user_id')
                ->whereRaw("{$transactionMonthExpression} = budgets.period")
                ->whereIn('e.category_id', static function (QueryBuilder $subQuery): QueryBuilder {
                    return $subQuery
                        ->select('ba.category_id')
                        ->from('budget_allocations as ba')
                        ->join('categories as c', 'c.id', '=', 'ba.category_id')
                        ->where('c.type', CategoryType::Expense->value)
                        ->whereColumn('ba.budget_id', 'budgets.id');
                }),
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
