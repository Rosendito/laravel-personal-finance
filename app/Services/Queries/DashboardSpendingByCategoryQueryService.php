<?php

declare(strict_types=1);

namespace App\Services\Queries;

use App\Data\Dashboard\CategoryTotalData;
use App\Enums\LedgerAccountType;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class DashboardSpendingByCategoryQueryService
{
    /**
     * @return Collection<int, CategoryTotalData>
     */
    public function totals(User $user, CarbonInterface $startAt, CarbonInterface $endAt): Collection
    {
        $rows = DB::query()
            ->from('ledger_entries as e')
            ->selectRaw('COALESCE(p.id, c.id) as category_id')
            ->selectRaw('COALESCE(p.name, c.name, \'Sin categoría\') as category_name')
            ->selectRaw('SUM(CASE WHEN COALESCE(e.amount_base, e.amount) > 0 THEN COALESCE(e.amount_base, e.amount) ELSE 0 END) as total')
            ->join('ledger_accounts as a', 'a.id', '=', 'e.account_id')
            ->join('ledger_transactions as t', 't.id', '=', 'e.transaction_id')
            ->leftJoin('categories as c', 'c.id', '=', 't.category_id')
            ->leftJoin('categories as p', 'p.id', '=', 'c.parent_id')
            ->where('t.user_id', $user->id)
            ->where('a.type', LedgerAccountType::EXPENSE->value)
            ->where(static function ($query) {
                $query
                    ->whereNull('t.category_id')
                    ->orWhereRaw('COALESCE(p.is_reportable, c.is_reportable) = 1');
            })
            ->whereBetween('t.effective_at', [
                $startAt->toDateString(),
                $endAt->toDateString(),
            ])
            ->groupByRaw('COALESCE(p.id, c.id)')
            ->groupByRaw('COALESCE(p.name, c.name, \'Sin categoría\')')
            ->havingRaw('total > 0')
            ->orderByDesc('total')
            ->get();

        return $rows->map(
            static fn(object $row): CategoryTotalData => new CategoryTotalData(
                categoryId: $row->category_id === null ? null : (int) $row->category_id,
                name: (string) $row->category_name,
                total: (string) $row->total,
            ),
        );
    }
}
