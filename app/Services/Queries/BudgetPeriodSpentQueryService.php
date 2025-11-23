<?php

declare(strict_types=1);

namespace App\Services\Queries;

use App\Enums\CategoryType;
use App\Enums\LedgerAccountType;
use App\Models\BudgetPeriod;
use Illuminate\Support\Facades\DB;

final class BudgetPeriodSpentQueryService
{
    public function total(BudgetPeriod $period): string
    {
        $categoryIds = DB::table('categories')
            ->where(function ($query) use ($period) {
                $query->where('budget_id', $period->budget_id)
                    ->orWhereIn('parent_id', function ($subQuery) use ($period) {
                        $subQuery->select('id')
                            ->from('categories')
                            ->where('budget_id', $period->budget_id);
                    });
            })
            ->where('type', CategoryType::Expense->value)
            ->pluck('id');

        $sum = DB::query()
            ->from('ledger_entries as e')
            ->selectRaw('COALESCE(SUM(COALESCE(e.amount_base, e.amount)), 0) as total')
            ->join('ledger_transactions as t', 't.id', '=', 'e.transaction_id')
            ->join('ledger_accounts as a', 'a.id', '=', 'e.account_id')
            ->whereIn('t.category_id', $categoryIds)
            ->whereBetween('t.effective_at', [$period->start_at, $period->end_at])
            ->where('a.type', LedgerAccountType::Expense->value)
            ->value('total');

        return bcadd((string) ($sum ?? '0'), '0', 6);
    }
}
