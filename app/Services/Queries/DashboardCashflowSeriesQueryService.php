<?php

declare(strict_types=1);

namespace App\Services\Queries;

use App\Data\Dashboard\CashflowSeriesData;
use App\Enums\LedgerAccountType;
use App\Models\User;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

final class DashboardCashflowSeriesQueryService
{
    public function dailySeries(User $user, CarbonInterface $startAt, CarbonInterface $endAt): CashflowSeriesData
    {
        $period = CarbonPeriod::create($startAt->toDateString(), $endAt->subDay()->toDateString());

        $labels = [];
        $expenses = [];
        $incomes = [];

        foreach ($period as $date) {
            $labels[] = $date->toDateString();
            $expenses[$date->toDateString()] = '0';
            $incomes[$date->toDateString()] = '0';
        }

        $rows = DB::query()
            ->from('ledger_entries as e')
            ->selectRaw('DATE(t.effective_at) as day')
            ->selectRaw('a.type as account_type')
            ->selectRaw('SUM(COALESCE(e.amount_base, e.amount)) as total')
            ->join('ledger_accounts as a', 'a.id', '=', 'e.account_id')
            ->join('ledger_transactions as t', 't.id', '=', 'e.transaction_id')
            ->where('t.user_id', $user->id)
            ->whereIn('a.type', [
                LedgerAccountType::EXPENSE->value,
                LedgerAccountType::INCOME->value,
            ])
            ->whereBetween('t.effective_at', [
                $startAt->toDateString(),
                $endAt->toDateString(),
            ])
            ->groupBy('day', 'a.type')
            ->get();

        foreach ($rows as $row) {
            $day = (string) $row->day;
            $total = (string) $row->total;

            if ($row->account_type === LedgerAccountType::EXPENSE->value) {
                $expenses[$day] = bcadd($expenses[$day] ?? '0', $this->positive($total), 6);

                continue;
            }

            // Income accounts are normally negative in entries, normalize to positive for visualization.
            $incomes[$day] = bcadd($incomes[$day] ?? '0', $this->positive($this->invertIfNegative($total)), 6);
        }

        return new CashflowSeriesData(
            labels: $labels,
            expenses: array_values($expenses),
            incomes: array_values($incomes),
        );
    }

    private function positive(string $value): string
    {
        return bccomp($value, '0', 6) >= 0 ? $value : '0';
    }

    private function invertIfNegative(string $value): string
    {
        if (str_starts_with($value, '-')) {
            return mb_substr($value, 1);
        }

        return $value;
    }
}
