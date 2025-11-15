<?php

declare(strict_types=1);

use App\Enums\LedgerAccountType;
use App\Models\Budget;
use App\Models\BudgetAllocation;
use App\Models\Category;
use App\Models\Currency;
use App\Models\LedgerAccount;
use App\Models\LedgerEntry;
use App\Models\LedgerTransaction;
use App\Models\User;
use Illuminate\Support\Facades\Date;

describe(BudgetAllocation::class, function (): void {
    it('links budgets, categories, and balanced transactions', function (): void {
        $user = User::factory()->create();
        $currency = Currency::factory()
            ->state([
                'code' => 'USD',
                'precision' => 2,
            ])
            ->create();

        $budget = Budget::factory()
            ->for($user)
            ->state([
                'name' => 'Demo Budget',
                'period' => Date::now()->format('Y-m'),
            ])
            ->create();

        $category = Category::factory()
            ->expense()
            ->for($user)
            ->state([
                'name' => 'Office Supplies',
            ])
            ->create();

        $allocation = BudgetAllocation::factory()
            ->forBudget($budget)
            ->forCategory($category)
            ->state([
                'currency_code' => $currency->code,
                'amount' => 1_200,
            ])
            ->create();

        $cashAccount = LedgerAccount::factory()
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
                'name' => 'Office Supplies',
            ])
            ->create();

        $transaction = LedgerTransaction::factory()
            ->for($user)
            ->state([
                'description' => 'Budgeted purchase',
                'effective_at' => Date::now(),
                'posted_at' => Date::now()->toDateString(),
            ])
            ->create();

        LedgerEntry::factory()
            ->for($transaction, 'transaction')
            ->for($cashAccount, 'account')
            ->state([
                'amount' => -500,
                'currency_code' => $currency->code,
            ])
            ->create();

        LedgerEntry::factory()
            ->for($transaction, 'transaction')
            ->for($expenseAccount, 'account')
            ->state([
                'amount' => 500,
                'currency_code' => $currency->code,
                'category_id' => $category->id,
            ])
            ->create();

        expect($transaction->fresh()->isBalanced())->toBeTrue();
        expect($budget->fresh()->allocations)->toHaveCount(1);
        expect($allocation->fresh()->category->is($category))->toBeTrue();
        expect($category->fresh()->entries()->count())->toBe(1);
    });
});
