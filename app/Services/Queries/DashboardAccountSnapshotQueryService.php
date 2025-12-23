<?php

declare(strict_types=1);

namespace App\Services\Queries;

use App\Data\Dashboard\DashboardSnapshotData;
use App\Enums\LedgerAccountSubType;
use App\Enums\LedgerAccountType;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

final class DashboardAccountSnapshotQueryService
{
    public function snapshot(User $user, CarbonInterface $asOf): DashboardSnapshotData
    {
        $rows = DB::query()
            ->from('ledger_entries as e')
            ->selectRaw('a.subtype')
            ->selectRaw('a.type')
            ->selectRaw('COALESCE(SUM(COALESCE(e.amount_base, e.amount)), 0) as total')
            ->join('ledger_accounts as a', 'a.id', '=', 'e.account_id')
            ->join('ledger_transactions as t', 't.id', '=', 'e.transaction_id')
            ->where('t.user_id', $user->id)
            ->where('a.is_archived', false)
            ->where('a.is_fundamental', false)
            ->where('t.effective_at', '<=', $asOf->toDateString())
            ->whereIn('a.type', [
                LedgerAccountType::ASSET->value,
                LedgerAccountType::LIABILITY->value,
            ])
            ->groupBy('a.subtype', 'a.type')
            ->get();

        $liquidity = '0';
        $loanReceivable = '0';
        $liabilities = '0';

        foreach ($rows as $row) {
            $subtype = $row->subtype;
            $type = $row->type;
            $total = (string) $row->total;

            if ($type === LedgerAccountType::ASSET->value && $subtype !== null) {
                $subtypeEnum = LedgerAccountSubType::tryFrom($subtype);

                if ($subtypeEnum !== null && $subtypeEnum->isLiquid()) {
                    $liquidity = bcadd($liquidity, $total, 6);

                    continue;
                }

                if ($subtypeEnum === LedgerAccountSubType::LOAN_RECEIVABLE) {
                    $loanReceivable = bcadd($loanReceivable, $total, 6);
                }
            }

            if ($type === LedgerAccountType::LIABILITY->value) {
                $liabilities = bcadd($liabilities, $total, 6);
            }
        }

        return new DashboardSnapshotData(
            liquidity: $liquidity,
            loanReceivable: $loanReceivable,
            liabilitiesOwed: $this->abs($liabilities),
        );
    }

    private function abs(string $value): string
    {
        if (str_starts_with($value, '-')) {
            return mb_substr($value, 1);
        }

        return $value;
    }
}
