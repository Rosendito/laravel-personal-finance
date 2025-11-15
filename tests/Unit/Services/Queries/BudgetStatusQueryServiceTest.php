<?php

declare(strict_types=1);

use App\Data\BudgetAllocationStatusData;
use App\Enums\LedgerAccountType;
use App\Models\Budget;
use App\Models\BudgetAllocation;
use App\Models\Category;
use App\Models\Currency;
use App\Models\LedgerAccount;
use App\Models\LedgerEntry;
use App\Models\LedgerTransaction;
use App\Models\User;
use App\Services\Queries\BudgetStatusQueryService;
use Carbon\CarbonImmutable;

describe(BudgetStatusQueryService::class, function (): void {
    beforeEach(function (): void {
        $this->service = new BudgetStatusQueryService();
        $this->user = User::factory()->create();
        $this->currency = Currency::factory()
            ->state([
                'code' => 'USD',
                'precision' => 2,
            ])
            ->create();

        $this->assetAccount = LedgerAccount::factory()
            ->for($this->user)
            ->ofType(LedgerAccountType::Asset)
            ->state([
                'currency_code' => $this->currency->code,
            ])
            ->create();

        $this->expenseAccount = LedgerAccount::factory()
            ->for($this->user)
            ->ofType(LedgerAccountType::Expense)
            ->state([
                'currency_code' => $this->currency->code,
            ])
            ->create();

        $this->foodCategory = Category::factory()
            ->expense()
            ->for($this->user)
            ->state(['name' => 'Food'])
            ->create();

        $this->rentCategory = Category::factory()
            ->expense()
            ->for($this->user)
            ->state(['name' => 'Rent'])
            ->create();

        $this->budget = Budget::factory()
            ->for($this->user)
            ->forPeriod('2025-11')
            ->state([
                'name' => 'November Budget',
            ])
            ->create();

        $this->foodAllocation = BudgetAllocation::factory()
            ->forBudget($this->budget)
            ->forCategory($this->foodCategory)
            ->state([
                'amount' => 300,
                'currency_code' => $this->currency->code,
            ])
            ->create();

        $this->rentAllocation = BudgetAllocation::factory()
            ->forBudget($this->budget)
            ->forCategory($this->rentCategory)
            ->state([
                'amount' => 800,
                'currency_code' => $this->currency->code,
            ])
            ->create();
    });

    it('summarizes budget status per allocation within the budget period', function (): void {
        $novemberIncome = LedgerTransaction::factory()
            ->for($this->user)
            ->state([
                'effective_at' => CarbonImmutable::parse('2025-11-05 08:00:00'),
            ])
            ->create();

        LedgerEntry::factory()
            ->for($novemberIncome, 'transaction')
            ->for($this->assetAccount, 'account')
            ->state([
                'amount' => -400,
                'currency_code' => $this->currency->code,
            ])
            ->create();

        LedgerEntry::factory()
            ->for($novemberIncome, 'transaction')
            ->for($this->expenseAccount, 'account')
            ->state([
                'amount' => 400,
                'currency_code' => $this->currency->code,
                'category_id' => $this->foodCategory->id,
            ])
            ->create();

        $novemberRent = LedgerTransaction::factory()
            ->for($this->user)
            ->state([
                'effective_at' => CarbonImmutable::parse('2025-11-10 12:00:00'),
            ])
            ->create();

        LedgerEntry::factory()
            ->for($novemberRent, 'transaction')
            ->for($this->assetAccount, 'account')
            ->state([
                'amount' => -600,
                'currency_code' => $this->currency->code,
            ])
            ->create();

        LedgerEntry::factory()
            ->for($novemberRent, 'transaction')
            ->for($this->expenseAccount, 'account')
            ->state([
                'amount' => 600,
                'currency_code' => $this->currency->code,
                'category_id' => $this->rentCategory->id,
            ])
            ->create();

        $decemberSpending = LedgerTransaction::factory()
            ->for($this->user)
            ->state([
                'effective_at' => CarbonImmutable::parse('2025-12-01 10:00:00'),
            ])
            ->create();

        LedgerEntry::factory()
            ->for($decemberSpending, 'transaction')
            ->for($this->assetAccount, 'account')
            ->state([
                'amount' => -100,
                'currency_code' => $this->currency->code,
            ])
            ->create();

        LedgerEntry::factory()
            ->for($decemberSpending, 'transaction')
            ->for($this->expenseAccount, 'account')
            ->state([
                'amount' => 100,
                'currency_code' => $this->currency->code,
                'category_id' => $this->foodCategory->id,
            ])
            ->create();

        $status = $this->service->periodStatus($this->user, '2025-11');

        $foodStatus = $status->firstWhere('category_id', $this->foodCategory->id);
        $rentStatus = $status->firstWhere('category_id', $this->rentCategory->id);

        expect($foodStatus)->toBeInstanceOf(BudgetAllocationStatusData::class);
        expect($foodStatus->budgeted)->toBe('300.000000');
        expect($foodStatus->spent)->toBe('400.000000');
        expect($foodStatus->remaining)->toBe('-100.000000');

        expect($rentStatus)->toBeInstanceOf(BudgetAllocationStatusData::class);
        expect($rentStatus->budgeted)->toBe('800.000000');
        expect($rentStatus->spent)->toBe('600.000000');
        expect($rentStatus->remaining)->toBe('200.000000');
    });

    it('adds a spent_amount subselect for eager loading aggregate data', function (): void {
        $novemberTransaction = LedgerTransaction::factory()
            ->for($this->user)
            ->state([
                'effective_at' => CarbonImmutable::parse('2025-11-15 15:00:00'),
            ])
            ->create();

        LedgerEntry::factory()
            ->for($novemberTransaction, 'transaction')
            ->for($this->assetAccount, 'account')
            ->state([
                'amount' => -500,
                'currency_code' => $this->currency->code,
            ])
            ->create();

        LedgerEntry::factory()
            ->for($novemberTransaction, 'transaction')
            ->for($this->expenseAccount, 'account')
            ->state([
                'amount' => 500,
                'currency_code' => $this->currency->code,
                'category_id' => $this->rentCategory->id,
            ])
            ->create();

        $query = Budget::query()->whereKey($this->budget->id);
        $this->service->addSpentAmountSubselect($query);

        $budgetWithAggregate = $query->first();

        expect(bccomp((string) $budgetWithAggregate->spent_amount, '500', 6))->toBe(0);
    });
});
