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
        // Currency 'USD' created by global setup
        $currency = Currency::query()->where('code', 'USD')->firstOrFail();

        $user = User::factory()->create();

        $assetAccount = LedgerAccount::factory()
            ->for($user)
            ->ofType(LedgerAccountType::ASSET)
            ->state([
                'currency_code' => $currency->code,
                'name' => 'Test Asset',
            ])
            ->create();

        $incomeAccount = LedgerAccount::factory()
            ->for($user)
            ->ofType(LedgerAccountType::INCOME)
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
                'amount_base' => 150.25,
                'currency_code' => $currency->code,
            ])
            ->create();

        expect($transaction->fresh()->isBalanced())->toBeFalse();

        LedgerEntry::factory()
            ->for($transaction, 'transaction')
            ->for($incomeAccount, 'account')
            ->state([
                'amount' => -150.25,
                'amount_base' => -150.25,
                'currency_code' => $currency->code,
            ])
            ->create();

        expect($transaction->fresh()->isBalanced())->toBeTrue();
    });

    it('evaluates double-entry balance on multi-currency transactions', function (): void {
        $usd = Currency::query()->where('code', 'USD')->firstOrFail();
        $ves = Currency::query()->firstOrCreate(['code' => 'VES'], ['precision' => 2]);

        $user = User::factory()->create();

        $usdAccount = LedgerAccount::factory()
            ->for($user)
            ->ofType(LedgerAccountType::ASSET)
            ->state([
                'currency_code' => $usd->code,
                'name' => 'USD Wallet',
            ])
            ->create();

        $vesAccount = LedgerAccount::factory()
            ->for($user)
            ->ofType(LedgerAccountType::EXPENSE)
            ->state([
                'currency_code' => $ves->code,
                'name' => 'VES Expense',
            ])
            ->create();

        $transaction = LedgerTransaction::factory()
            ->for($user)
            ->state([
                'description' => 'Exchange Transaction',
                'effective_at' => Date::now(),
                'posted_at' => Date::now()->toDateString(),
            ])
            ->create();

        // Entry 1: 100 USD
        LedgerEntry::factory()
            ->for($transaction, 'transaction')
            ->for($usdAccount, 'account')
            ->state([
                'amount' => 100.00,
                'amount_base' => 100.00,
                'currency_code' => $usd->code,
            ])
            ->create();

        // Entry 2: -33000 VES (equivalent to -100 USD base)
        LedgerEntry::factory()
            ->for($transaction, 'transaction')
            ->for($vesAccount, 'account')
            ->state([
                'amount' => -33000.00,
                'amount_base' => -100.00,
                'currency_code' => $ves->code,
            ])
            ->create();

        // Should be balanced because amount_base sums to 0 (100 - 100)
        // But amount sums to -32900 (100 - 33000)
        expect($transaction->fresh()->isBalanced())->toBeTrue();
    });

    it('derives account balances from ledger entries', function (): void {
        // Currency 'USD' created by global setup
        $currency = Currency::query()->where('code', 'USD')->firstOrFail();

        $user = User::factory()->create();

        $assetAccount = LedgerAccount::factory()
            ->for($user)
            ->ofType(LedgerAccountType::ASSET)
            ->state([
                'currency_code' => $currency->code,
                'name' => 'Cash',
            ])
            ->create();

        $expenseAccount = LedgerAccount::factory()
            ->for($user)
            ->ofType(LedgerAccountType::EXPENSE)
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
