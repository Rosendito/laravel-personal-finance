<?php

declare(strict_types=1);

namespace App\Services\Queries;

use App\Data\AccountBalanceData;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class AccountBalanceQueryService
{
    /**
     * @return Collection<int, AccountBalanceData>
     */
    public function totalsForUser(User $user, ?CarbonInterface $asOf = null): Collection
    {
        $entryTotals = DB::query()
            ->from('ledger_entries as e')
            ->select('e.account_id')
            ->selectRaw('COALESCE(SUM(e.amount), 0) as balance')
            ->join('ledger_transactions as t', 't.id', '=', 'e.transaction_id')
            ->where('t.user_id', $user->id)
            ->when($asOf, static fn (QueryBuilder $query, CarbonInterface $asOfDate): QueryBuilder => $query
                ->where('t.effective_at', '<=', $asOfDate))
            ->groupBy('e.account_id');

        $rows = DB::query()
            ->from('ledger_accounts as a')
            ->select([
                'a.id',
                'a.name',
                'a.currency_code',
                'a.is_fundamental',
                'a.subtype',
            ])
            ->selectRaw('COALESCE(balances.balance, 0) as balance')
            ->leftJoinSub($entryTotals, 'balances', 'balances.account_id', '=', 'a.id')
            ->where('a.user_id', $user->id)
            ->orderBy('a.name')
            ->get();

        return $rows->map(
            fn (object $row): AccountBalanceData => new AccountBalanceData(
                account_id: (int) $row->id,
                name: $row->name,
                currency_code: $row->currency_code,
                balance: bcadd($this->normalizeNumber($row->balance ?? '0'), '0', 6),
                is_fundamental: (bool) $row->is_fundamental,
                subtype: $row->subtype,
            )
        );
    }

    public function balanceForAccount(int $accountId): string
    {
        $balance = DB::query()
            ->from('ledger_entries')
            ->where('account_id', $accountId)
            ->sum('amount');

        return bcadd($this->normalizeNumber($balance), '0', 6);
    }

    private function normalizeNumber(int|float|string|null $value): string
    {
        $numericString = is_string($value) ? mb_trim($value) : (string) ($value ?? '0');

        if (is_numeric($numericString) && ! str_contains($numericString, 'e') && ! str_contains($numericString, 'E')) {
            return $numericString;
        }

        return number_format((float) ($value ?? 0), 6, '.', '');
    }
}
