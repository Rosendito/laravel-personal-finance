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
        $sum = DB::query()
            ->from('ledger_entries as e')
            ->selectRaw('COALESCE(SUM(COALESCE(e.amount_base, e.amount)), 0) as total')
            ->join('ledger_transactions as t', 't.id', '=', 'e.transaction_id')
            ->join('ledger_accounts as a', 'a.id', '=', 'e.account_id')
            ->join('categories as c', 'c.id', '=', 't.category_id')
            ->where('t.budget_period_id', $period->id)
            ->where('c.type', CategoryType::Expense->value)
            ->where('a.type', LedgerAccountType::Expense->value)
            ->value('total');

        return bcadd((string) ($sum ?? '0'), '0', 6);
    }
}
