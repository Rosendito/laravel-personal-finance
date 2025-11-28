<?php

declare(strict_types=1);

use App\Enums\LedgerAccountType;
use App\Models\Category;
use App\Models\Currency;
use App\Models\LedgerAccount;
use App\Models\LedgerEntry;
use App\Models\LedgerTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe(Category::class, function (): void {
    it('calculates the balance using entries whose account type matches the category', function (): void {
        $currencyCode = 'USD';

        config()->set('finance.currency.default', $currencyCode);

        Currency::query()->firstOrCreate(
            ['code' => $currencyCode],
            ['precision' => 2],
        );

        $user = User::factory()->create();

        $expenseCategory = Category::factory()
            ->expense()
            ->for($user)
            ->create();

        $incomeCategory = Category::factory()
            ->income()
            ->for($user)
            ->create();

        $assetAccount = LedgerAccount::factory()
            ->for($user)
            ->ofType(LedgerAccountType::ASSET)
            ->state(['currency_code' => $currencyCode])
            ->create();

        $expenseAccount = LedgerAccount::factory()
            ->for($user)
            ->ofType(LedgerAccountType::EXPENSE)
            ->state(['currency_code' => $currencyCode])
            ->create();

        $incomeAccount = LedgerAccount::factory()
            ->for($user)
            ->ofType(LedgerAccountType::INCOME)
            ->state(['currency_code' => $currencyCode])
            ->create();

        $expenseTransaction = LedgerTransaction::factory()
            ->for($user)
            ->withCategory($expenseCategory)
            ->create();

        LedgerEntry::factory()
            ->for($expenseTransaction, 'transaction')
            ->for($assetAccount, 'account')
            ->state([
                'amount' => '-75.500000',
                'amount_base' => '-75.500000',
                'currency_code' => $currencyCode,
            ])
            ->create();

        LedgerEntry::factory()
            ->for($expenseTransaction, 'transaction')
            ->for($expenseAccount, 'account')
            ->state([
                'amount' => '75.500000',
                'amount_base' => '75.500000',
                'currency_code' => $currencyCode,
            ])
            ->create();

        $incomeTransaction = LedgerTransaction::factory()
            ->for($user)
            ->withCategory($incomeCategory)
            ->create();

        LedgerEntry::factory()
            ->for($incomeTransaction, 'transaction')
            ->for($assetAccount, 'account')
            ->state([
                'amount' => '200.000000',
                'amount_base' => '200.000000',
                'currency_code' => $currencyCode,
            ])
            ->create();

        LedgerEntry::factory()
            ->for($incomeTransaction, 'transaction')
            ->for($incomeAccount, 'account')
            ->state([
                'amount' => '-200.000000',
                'amount_base' => '-200.000000',
                'currency_code' => $currencyCode,
            ])
            ->create();

        $categories = Category::query()
            ->whereIn('id', [$expenseCategory->id, $incomeCategory->id])
            ->withBalance()
            ->get()
            ->keyBy('id');

        $expenseBalance = (string) $categories->get($expenseCategory->id)->balance;
        $incomeBalance = (string) $categories->get($incomeCategory->id)->balance;

        expect(bccomp($expenseBalance, '75.5', 3))->toBe(0)
            ->and(bccomp($incomeBalance, '-200', 3))->toBe(0);
    });
});
