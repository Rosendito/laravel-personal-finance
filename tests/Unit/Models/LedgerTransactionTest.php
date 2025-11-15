<?php

declare(strict_types=1);

use App\Enums\LedgerAccountType;
use App\Models\Currency;
use App\Models\LedgerAccount;
use App\Models\LedgerEntry;
use App\Models\LedgerTransaction;
use App\Models\User;
use Illuminate\Support\Facades\Date;

describe(LedgerTransaction::class, function (): void {
    it('evaluates double-entry balance on transactions', function (): void {
        $user = User::factory()->create();
        $currency = Currency::factory()
            ->state([
                'code' => 'USD',
                'precision' => 2,
            ])
            ->create();

        $assetAccount = LedgerAccount::factory()
            ->for($user)
            ->ofType(LedgerAccountType::Asset)
            ->state([
                'currency_code' => $currency->code,
                'name' => 'Test Asset',
            ])
            ->create();

        $incomeAccount = LedgerAccount::factory()
            ->for($user)
            ->ofType(LedgerAccountType::Income)
            ->state([
                'currency_code' => $currency->code,
                'name' => 'Test Income',
            ])
            ->create();

        $transaction = LedgerTransaction::factory()
            ->for($user)
            ->state([
                'description' => 'Test Transaction',
                'effective_at' => Date::now(),
                'posted_at' => Date::now()->toDateString(),
            ])
            ->create();

        LedgerEntry::factory()
            ->for($transaction, 'transaction')
            ->for($assetAccount, 'account')
            ->state([
                'amount' => 150.25,
                'currency_code' => $currency->code,
            ])
            ->create();

        expect($transaction->fresh()->isBalanced())->toBeFalse();

        LedgerEntry::factory()
            ->for($transaction, 'transaction')
            ->for($incomeAccount, 'account')
            ->state([
                'amount' => -150.25,
                'currency_code' => $currency->code,
            ])
            ->create();

        expect($transaction->fresh()->isBalanced())->toBeTrue();
    });

    it('derives account balances from ledger entries', function (): void {
        $user = User::factory()->create();
        $currency = Currency::factory()
            ->state([
                'code' => 'USD',
                'precision' => 2,
            ])
            ->create();

        $assetAccount = LedgerAccount::factory()
            ->for($user)
            ->ofType(LedgerAccountType::Asset)
            ->state([
                'currency_code' => $currency->code,
                'name' => 'Cash',
            ])
            ->create();

        $expenseAccount = LedgerAccount::factory()
            ->for($user)
            ->ofType(LedgerAccountType::Expense)
            ->state([
                'currency_code' => $currency->code,
                'name' => 'Supplies',
            ])
            ->create();

        $transaction = LedgerTransaction::factory()
            ->for($user)
            ->state([
                'description' => 'Supplies purchase',
                'effective_at' => Date::now(),
                'posted_at' => Date::now()->toDateString(),
            ])
            ->create();

        LedgerEntry::factory()
            ->for($transaction, 'transaction')
            ->for($assetAccount, 'account')
            ->state([
                'amount' => 800,
                'currency_code' => $currency->code,
            ])
            ->create();

        LedgerEntry::factory()
            ->for($transaction, 'transaction')
            ->for($expenseAccount, 'account')
            ->state([
                'amount' => -800,
                'currency_code' => $currency->code,
            ])
            ->create();

        $balance = $assetAccount->entries()
            ->whereHas('transaction', fn ($query) => $query->where('effective_at', '<=', Date::now()))
            ->sum('amount');

        expect((float) $balance)->toBe(800.0);
    });
});
