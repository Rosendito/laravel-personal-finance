<?php

declare(strict_types=1);

namespace App\Services\Queries;

use App\Data\Dashboard\RecentTransactionData;
use App\Enums\LedgerAccountType;
use App\Models\LedgerTransaction;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class DashboardRecentTransactionsQueryService
{
    /**
     * @return Collection<int, RecentTransactionData>
     */
    public function recent(User $user, CarbonInterface $startAt, CarbonInterface $endAt, int $limit = 15): Collection
    {
        $amounts = DB::query()
            ->from('ledger_entries as e')
            ->selectRaw('e.transaction_id')
            ->selectRaw('SUM(CASE WHEN a.type = ? THEN COALESCE(e.amount_base, e.amount) ELSE 0 END) as expense_total', [LedgerAccountType::EXPENSE->value])
            ->selectRaw('SUM(CASE WHEN a.type = ? THEN COALESCE(e.amount_base, e.amount) ELSE 0 END) as income_total', [LedgerAccountType::INCOME->value])
            ->join('ledger_accounts as a', 'a.id', '=', 'e.account_id')
            ->join('ledger_transactions as t', 't.id', '=', 'e.transaction_id')
            ->where('t.user_id', $user->id)
            ->whereBetween('t.effective_at', [
                $startAt->toDateString(),
                $endAt->toDateString(),
            ])
            ->groupBy('e.transaction_id')
            ->get()
            ->keyBy('transaction_id')
            ->map(static function (object $row): array {
                $expense = (string) ($row->expense_total ?? '0');
                $incomeRaw = (string) ($row->income_total ?? '0');

                return [
                    'expense' => $expense,
                    'income' => str_starts_with($incomeRaw, '-') ? mb_substr($incomeRaw, 1) : $incomeRaw,
                ];
            });

        return LedgerTransaction::query()
            ->with(['category', 'budgetPeriod'])
            ->where('user_id', $user->id)
            ->whereBetween('effective_at', [
                $startAt->toDateString(),
                $endAt->toDateString(),
            ])
            ->orderByDesc('effective_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(function (LedgerTransaction $transaction) use ($amounts): RecentTransactionData {
                $totals = $amounts->get($transaction->id, ['expense' => '0', 'income' => '0']);

                return new RecentTransactionData(
                    transactionId: $transaction->id,
                    effectiveAt: $transaction->effective_at,
                    description: $transaction->description,
                    categoryName: $transaction->category?->name,
                    budgetPeriodLabel: $transaction->budgetPeriod?->start_at?->toDateString() !== null
                        ? sprintf('%s → %s', $transaction->budgetPeriod->start_at->toDateString(), $transaction->budgetPeriod->end_at->toDateString())
                        : null,
                    expenseAmount: $totals['expense'],
                    incomeAmount: $totals['income'],
                );
            });
    }
}
