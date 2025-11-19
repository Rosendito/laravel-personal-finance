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
            ])
            ->selectRaw('COALESCE(balances.balance, 0) as balance')
            ->leftJoinSub($entryTotals, 'balances', 'balances.account_id', '=', 'a.id')
            ->where('a.user_id', $user->id)
            ->orderBy('a.name')
            ->get();

        return $rows->map(
            static fn ($row): AccountBalanceData => new AccountBalanceData(
                account_id: (int) $row->id,
                name: $row->name,
                currency_code: $row->currency_code,
                balance: bcadd((string) ($row->balance ?? '0'), '0', 6),
                is_fundamental: (bool) $row->is_fundamental,
            )
        );
    }

    public function balanceForAccount(int $accountId): string
    {
        $balance = DB::query()
            ->from('ledger_entries')
            ->where('account_id', $accountId)
            ->sum('amount');

        return bcadd((string) ($balance ?? '0'), '0', 6);
    }
}
