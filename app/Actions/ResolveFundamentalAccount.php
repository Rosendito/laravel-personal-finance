<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\LedgerAccountType;
use App\Exceptions\LedgerIntegrityException;
use App\Models\LedgerAccount;
use App\Models\User;

final class ResolveFundamentalAccount
{
    public function __construct(
        private readonly EnsureFundamentalAccounts $ensureFundamentalAccounts,
    ) {}

    public function execute(User $user, string $currencyCode, LedgerAccountType $type): LedgerAccount
    {
        $this->ensureFundamentalAccounts->execute($user, $currencyCode);

        $account = LedgerAccount::query()
            ->where('user_id', $user->id)
            ->where('currency_code', $currencyCode)
            ->where('type', $type)
            ->where('is_fundamental', true)
            ->first();

        if ($account === null) {
            throw LedgerIntegrityException::fundamentalAccountNotFound(
                $user->id,
                $currencyCode,
                $type,
            );
        }

        return $account;
    }
}
