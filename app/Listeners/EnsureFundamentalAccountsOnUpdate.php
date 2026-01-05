<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Actions\EnsureFundamentalAccounts;
use App\Events\LedgerAccountUpdated;

final readonly class EnsureFundamentalAccountsOnUpdate
{
    public function __construct(
        private EnsureFundamentalAccounts $ensureFundamentalAccounts,
    ) {}

    public function handle(LedgerAccountUpdated $event): void
    {
        $account = $event->account;
        $oldCurrencyCode = $event->oldCurrencyCode;

        // Only ensure fundamental accounts if currency changed
        if ($oldCurrencyCode === null || $oldCurrencyCode === $account->currency_code) {
            return;
        }

        $user = $account->user;

        if ($user === null) {
            return;
        }

        // Ensure fundamental accounts for the new currency
        $this->ensureFundamentalAccounts->execute($user, $account->currency_code);
    }
}
