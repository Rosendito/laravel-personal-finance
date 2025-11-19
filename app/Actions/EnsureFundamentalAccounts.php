<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\LedgerAccountType;
use App\Models\LedgerAccount;
use App\Models\User;

final class EnsureFundamentalAccounts
{
    public function execute(User $user, string $currencyCode): void
    {
        $accounts = [
            [
                'name' => 'External Expenses',
                'type' => LedgerAccountType::Expense,
            ],
            [
                'name' => 'External Income',
                'type' => LedgerAccountType::Income,
            ],
        ];

        foreach ($accounts as $accountData) {
            // We append the currency code to the name if it's not the default one,
            // OR we just rely on the user + currency + type uniqueness?
            // The previous requirement was "External Expenses" (generic name).
            // If we have multiple currencies, we can't have 2 accounts named "External Expenses" for the same user
            // because of the unique constraint ['user_id', 'name'].
            // So we MUST suffix the name: "External Expenses (EUR)".

            $accountName = $accountData['name'];
            if ($currencyCode !== 'USD') {
                $accountName = sprintf('%s (%s)', $accountData['name'], $currencyCode);
            }

            // Check if account already exists before creating
            $exists = LedgerAccount::where('user_id', $user->id)
                ->where('name', $accountName)
                ->where('type', $accountData['type'])
                ->where('currency_code', $currencyCode)
                ->where('is_fundamental', true)
                ->exists();

            if ($exists) {
                continue;
            }

            LedgerAccount::create([
                'user_id' => $user->id,
                'name' => $accountName,
                'type' => $accountData['type'],
                'currency_code' => $currencyCode,
                'is_archived' => false,
                'is_fundamental' => true,
            ]);
        }
    }
}
