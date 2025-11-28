<?php

declare(strict_types=1);

namespace App\Services\Queries;

use App\Data\IncomeStatementSummaryData;
use App\Enums\LedgerAccountType;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

final class IncomeStatementQueryService
{
    public function summarize(User $user, CarbonInterface $startDate, CarbonInterface $endDate): IncomeStatementSummaryData
    {
        $results = DB::query()
            ->from('ledger_entries as e')
            ->join('ledger_accounts as a', 'a.id', '=', 'e.account_id')
            ->join('ledger_transactions as t', 't.id', '=', 'e.transaction_id')
            ->where('a.user_id', $user->id)
            ->whereBetween('t.effective_at', [$startDate, $endDate])
            ->selectRaw(
                <<<'SQL'
                SUM(CASE WHEN a.type = ? THEN -e.amount ELSE 0 END) AS total_income,
                SUM(CASE WHEN a.type = ? THEN  e.amount ELSE 0 END) AS total_expense,
                SUM(CASE
                        WHEN a.type = ? THEN -e.amount
                        WHEN a.type = ? THEN -e.amount
                        ELSE 0 END) AS net_income
                SQL,
                [
                    LedgerAccountType::INCOME->value,
                    LedgerAccountType::EXPENSE->value,
                    LedgerAccountType::INCOME->value,
                    LedgerAccountType::EXPENSE->value,
                ]
            )
            ->first();

        $totalIncome = $results?->total_income ?? '0';
        $totalExpense = $results?->total_expense ?? '0';
        $netIncome = $results?->net_income ?? '0';

        return new IncomeStatementSummaryData(
            total_income: bcadd((string) $totalIncome, '0', 6),
            total_expense: bcadd((string) $totalExpense, '0', 6),
            net_income: bcadd((string) $netIncome, '0', 6),
        );
    }
}
