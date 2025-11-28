<?php

declare(strict_types=1);

namespace App\Services\Queries;

use App\Enums\LedgerAccountType;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class NetWorthQueryService
{
    /**
     * Calculate net worth for a user: Asset + Equity - Liability
     * Uses amount_base when available, falling back to amount
     */
    public function calculateForUser(User $user): string
    {
        // Calculate Asset + Equity total
        $assetEquityTotal = DB::query()
            ->from('ledger_entries as e')
            ->join('ledger_accounts as a', 'a.id', '=', 'e.account_id')
            ->join('ledger_transactions as t', 't.id', '=', 'e.transaction_id')
            ->where('t.user_id', $user->id)
            ->where('a.is_archived', false)
            ->whereIn('a.type', [LedgerAccountType::ASSET->value, LedgerAccountType::EQUITY->value])
            ->selectRaw('COALESCE(SUM(COALESCE(e.amount_base, e.amount)), 0) as total')
            ->value('total') ?? '0';

        // Calculate Liability total
        $liabilityTotal = DB::query()
            ->from('ledger_entries as e')
            ->join('ledger_accounts as a', 'a.id', '=', 'e.account_id')
            ->join('ledger_transactions as t', 't.id', '=', 'e.transaction_id')
            ->where('t.user_id', $user->id)
            ->where('a.is_archived', false)
            ->where('a.type', LedgerAccountType::LIABILITY->value)
            ->selectRaw('COALESCE(SUM(COALESCE(e.amount_base, e.amount)), 0) as total')
            ->value('total') ?? '0';

        // Calculate net worth: Asset + Equity - Liability
        return bcsub((string) $assetEquityTotal, (string) $liabilityTotal, 6);
    }
}
