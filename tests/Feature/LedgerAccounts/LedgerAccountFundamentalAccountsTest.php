<?php

declare(strict_types=1);

use App\Actions\EnsureFundamentalAccounts;
use App\Enums\LedgerAccountType;
use App\Models\Currency;
use App\Models\LedgerAccount;
use App\Models\User;

describe('LedgerAccount Fundamental Accounts', function (): void {
    it('creates fundamental accounts when a new account is created', function (): void {
        Currency::factory()->create(['code' => 'EUR']);
        $user = User::factory()->create();

        // Create a new account in EUR
        $account = LedgerAccount::factory()
            ->for($user)
            ->ofType(LedgerAccountType::Asset)
            ->state(['currency_code' => 'EUR'])
            ->create();

        // Verify fundamental accounts were created
        $expenseAccount = LedgerAccount::where('user_id', $user->id)
            ->where('currency_code', 'EUR')
            ->where('type', LedgerAccountType::Expense)
            ->where('name', 'External Expenses (EUR)')
            ->first();

        $incomeAccount = LedgerAccount::where('user_id', $user->id)
            ->where('currency_code', 'EUR')
            ->where('type', LedgerAccountType::Income)
            ->where('name', 'External Income (EUR)')
            ->first();

        expect($expenseAccount)->not->toBeNull()
            ->and($incomeAccount)->not->toBeNull();
    });

    it('creates fundamental accounts when currency is updated', function (): void {
        Currency::factory()->create(['code' => 'EUR']);
        Currency::factory()->create(['code' => 'GBP']);

        $user = User::factory()->create();

        // Create account in EUR
        $account = LedgerAccount::factory()
            ->for($user)
            ->ofType(LedgerAccountType::Asset)
            ->state(['currency_code' => 'EUR'])
            ->create();

        // Update to GBP
        $account->currency_code = 'GBP';
        $account->save();

        // Verify fundamental accounts were created for GBP
        $expenseAccount = LedgerAccount::where('user_id', $user->id)
            ->where('currency_code', 'GBP')
            ->where('type', LedgerAccountType::Expense)
            ->where('name', 'External Expenses (GBP)')
            ->first();

        $incomeAccount = LedgerAccount::where('user_id', $user->id)
            ->where('currency_code', 'GBP')
            ->where('type', LedgerAccountType::Income)
            ->where('name', 'External Income (GBP)')
            ->first();

        expect($expenseAccount)->not->toBeNull()
            ->and($incomeAccount)->not->toBeNull();
    });

    it('does not create fundamental accounts if currency did not change', function (): void {
        Currency::factory()->create(['code' => 'EUR']);
        $user = User::factory()->create();

        $account = LedgerAccount::factory()
            ->for($user)
            ->ofType(LedgerAccountType::Asset)
            ->state(['currency_code' => 'EUR'])
            ->create();

        // Count before update
        $countBefore = LedgerAccount::where('user_id', $user->id)
            ->where('currency_code', 'EUR')
            ->count();

        // Update name but not currency
        $account->name = 'Updated Name';
        $account->save();

        // Count should be the same (no new fundamental accounts)
        $countAfter = LedgerAccount::where('user_id', $user->id)
            ->where('currency_code', 'EUR')
            ->count();

        expect($countAfter)->toBe($countBefore);
    });

    it('does not duplicate fundamental accounts if they already exist', function (): void {
        Currency::factory()->create(['code' => 'EUR']);
        $user = User::factory()->create();

        // Manually create fundamental accounts
        $ensureFundamentalAccounts = app(EnsureFundamentalAccounts::class);
        $ensureFundamentalAccounts->execute($user, 'EUR');

        $countBefore = LedgerAccount::where('user_id', $user->id)
            ->where('currency_code', 'EUR')
            ->count();

        // Create a new account - should trigger listener but not create duplicates
        LedgerAccount::factory()
            ->for($user)
            ->ofType(LedgerAccountType::Asset)
            ->state(['currency_code' => 'EUR'])
            ->create();

        $countAfter = LedgerAccount::where('user_id', $user->id)
            ->where('currency_code', 'EUR')
            ->count();

        // Should only increase by 1 (the new asset account)
        expect($countAfter)->toBe($countBefore + 1);
    });
});
