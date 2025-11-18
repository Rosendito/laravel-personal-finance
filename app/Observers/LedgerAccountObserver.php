<?php

declare(strict_types=1);

namespace App\Observers;

use App\Actions\EnsureFundamentalAccounts;
use App\Models\LedgerAccount;
use Illuminate\Support\Str;

final class LedgerAccountObserver
{
    public function __construct(
        private readonly EnsureFundamentalAccounts $ensureFundamentalAccounts,
    ) {}

    /**
     * Handle the LedgerAccount "created" event.
     */
    public function created(LedgerAccount $ledgerAccount): void
    {
        // Avoid infinite loops and only react to "Real" accounts (Asset/Liability/Equity).
        // Assuming Fundamental accounts are EXPENSE/INCOME.
        // If user creates a manual Expense/Income account, we should also ensure the fundamental ones exist
        // but we must check if the created account IS one of the fundamental ones to avoid recursion.
        
        if (Str::startsWith($ledgerAccount->name, 'External Expenses') || Str::startsWith($ledgerAccount->name, 'External Income')) {
            return;
        }

        // Load user if not loaded (it should be, but safety first)
        $user = $ledgerAccount->user;
        if ($user === null) {
             // Should not happen given our schema, but strict types...
             return;
        }

        $this->ensureFundamentalAccounts->execute($user, $ledgerAccount->currency_code);
    }
}
