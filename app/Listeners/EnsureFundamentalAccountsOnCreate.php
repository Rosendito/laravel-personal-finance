<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Actions\EnsureFundamentalAccounts;
use App\Events\LedgerAccountCreated;

final class EnsureFundamentalAccountsOnCreate
{
    public function __construct(
        private readonly EnsureFundamentalAccounts $ensureFundamentalAccounts,
    ) {}

    public function handle(LedgerAccountCreated $event): void
    {
        $account = $event->account;
        $user = $account->user;

        if ($user === null) {
            return;
        }

        $this->ensureFundamentalAccounts->execute($user, $account->currency_code);
    }
}
