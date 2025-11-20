<?php

declare(strict_types=1);

use App\Enums\LedgerAccountType;
use App\Models\Budget;
use App\Models\BudgetPeriod;
use App\Models\Category;
use App\Models\Currency;
use App\Models\LedgerAccount;
use App\Models\LedgerEntry;
use App\Models\LedgerTransaction;
use App\Models\User;
use App\Services\Queries\BudgetPeriodSpentQueryService;

describe(BudgetPeriodSpentQueryService::class, function (): void {
    beforeEach(function (): void {
        $this->service = new BudgetPeriodSpentQueryService();
        $this->user = User::factory()->create();

        $this->currency = Currency::query()->updateOrCreate(
            ['code' => 'USD'],
            ['precision' => 2],
        );

        $this->budget = Budget::factory()
            ->for($this->user)
            ->create();

        $this->period = BudgetPeriod::factory()
            ->for($this->budget)
            ->state([
                'start_at' => '2025-01-01',
                'end_at' => '2025-02-01',
                'amount' => '1000',
            ])
            ->create();

        $this->expenseCategory = Category::factory()
            ->expense()
            ->for($this->user)
            ->create();

        $this->incomeCategory = Category::factory()
            ->income()
            ->for($this->user)
            ->create();

        $this->assetAccount = LedgerAccount::factory()
            ->for($this->user)
            ->ofType(LedgerAccountType::Asset)
            ->state(['currency_code' => $this->currency->code])
            ->create();

        $this->expenseAccount = LedgerAccount::factory()
            ->for($this->user)
            ->ofType(LedgerAccountType::Expense)
            ->state(['currency_code' => $this->currency->code])
            ->create();

        $this->incomeAccount = LedgerAccount::factory()
            ->for($this->user)
            ->ofType(LedgerAccountType::Income)
            ->state(['currency_code' => $this->currency->code])
            ->create();
    });

    it('sums only expense account entries for budget period expenses', function (): void {
        $expenseTransaction = LedgerTransaction::factory()
            ->for($this->user)
            ->state([
                'budget_period_id' => $this->period->id,
            ])
            ->withCategory($this->expenseCategory)
            ->create();

        LedgerEntry::factory()
            ->for($expenseTransaction, 'transaction')
            ->for($this->assetAccount, 'account')
            ->state([
                'amount' => '-120.000000',
                'amount_base' => '-120.000000',
                'currency_code' => $this->currency->code,
            ])
            ->create();

        LedgerEntry::factory()
            ->for($expenseTransaction, 'transaction')
            ->for($this->expenseAccount, 'account')
            ->state([
                'amount' => '120.000000',
                'amount_base' => '120.000000',
                'currency_code' => $this->currency->code,
            ])
            ->create();

        $secondExpenseTransaction = LedgerTransaction::factory()
            ->for($this->user)
            ->state([
                'budget_period_id' => $this->period->id,
            ])
            ->withCategory($this->expenseCategory)
            ->create();

        LedgerEntry::factory()
            ->for($secondExpenseTransaction, 'transaction')
            ->for($this->assetAccount, 'account')
            ->state([
                'amount' => '-55.500000',
                'amount_base' => null,
                'currency_code' => $this->currency->code,
            ])
            ->create();

        LedgerEntry::factory()
            ->for($secondExpenseTransaction, 'transaction')
            ->for($this->expenseAccount, 'account')
            ->state([
                'amount' => '55.500000',
                'amount_base' => null,
                'currency_code' => $this->currency->code,
            ])
            ->create();

        $incomeTransaction = LedgerTransaction::factory()
            ->for($this->user)
            ->state([
                'budget_period_id' => $this->period->id,
            ])
            ->withCategory($this->incomeCategory)
            ->create();

        LedgerEntry::factory()
            ->for($incomeTransaction, 'transaction')
            ->for($this->assetAccount, 'account')
            ->state([
                'amount' => '300.000000',
                'amount_base' => '300.000000',
                'currency_code' => $this->currency->code,
            ])
            ->create();

        LedgerEntry::factory()
            ->for($incomeTransaction, 'transaction')
            ->for($this->incomeAccount, 'account')
            ->state([
                'amount' => '-300.000000',
                'amount_base' => '-300.000000',
                'currency_code' => $this->currency->code,
            ])
            ->create();

        $total = $this->service->total($this->period);

        expect($total)->toBe('175.500000');
    });
});
