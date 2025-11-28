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
                'type' => LedgerAccountType::EXPENSE,
            ],
            [
                'name' => 'External Income',
                'type' => LedgerAccountType::INCOME,
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
            // Always append currency code if it's not the default one, OR if we want consistency?
            // The test expects "External Expenses (EUR)" for EUR.
            // But for USD (default), it expects "External Expenses".

            // Let's check the config default currency instead of hardcoding 'USD'
            $defaultCurrency = config('finance.currency.default', 'USD');

            if ($currencyCode !== $defaultCurrency) {
                $accountName = sprintf('%s (%s)', $accountData['name'], $currencyCode);
            }

            // Check if account already exists before creating
            // We check by name AND type AND currency AND fundamental flag to be sure.
            // But wait, if we changed the name logic, we might not find the old one if we only check by name.
            // The previous check was:
            // ->where('name', $accountName)
            // ->where('type', $accountData['type'])
            // ->where('currency_code', $currencyCode)
            // ->where('is_fundamental', true)

            // If I removed the name check, it might match ANY fundamental account of that type/currency.
            // Which is probably what we want: "Does a fundamental Expense account in EUR exist for this user?"
            // If yes, don't create another one.

            $exists = LedgerAccount::where('user_id', $user->id)
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
