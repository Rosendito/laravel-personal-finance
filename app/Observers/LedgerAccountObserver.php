<?php

declare(strict_types=1);

namespace App\Observers;

use App\Events\LedgerAccountCreated;
use App\Events\LedgerAccountUpdated;
use App\Exceptions\LedgerIntegrityException;
use App\Models\LedgerAccount;

final class LedgerAccountObserver
{
    /**
     * Handle the LedgerAccount "created" event.
     */
    public function created(LedgerAccount $ledgerAccount): void
    {
        // Avoid infinite loops - don't emit event for fundamental accounts
        if ($ledgerAccount->is_fundamental) {
            return;
        }

        event(new LedgerAccountCreated($ledgerAccount));
    }

    /**
     * Handle the LedgerAccount "updated" event.
     */
    public function updated(LedgerAccount $ledgerAccount): void
    {
        // Avoid infinite loops - don't emit event for fundamental accounts
        if ($ledgerAccount->is_fundamental) {
            return;
        }

        $oldCurrencyCode = $ledgerAccount->getOriginal('currency_code');

        event(new LedgerAccountUpdated($ledgerAccount, $oldCurrencyCode));
    }

    /**
     * Handle the LedgerAccount "deleting" event.
     */
    public function deleting(LedgerAccount $ledgerAccount): void
    {
        // Prevent deletion of fundamental accounts
        if ($ledgerAccount->is_fundamental) {
            throw LedgerIntegrityException::cannotDeleteFundamentalAccount();
        }

        // Prevent deletion of accounts with entries
        if ($ledgerAccount->entries()->exists()) {
            throw LedgerIntegrityException::cannotDeleteAccountWithEntries();
        }
    }
}
