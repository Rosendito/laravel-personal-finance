<?php

declare(strict_types=1);

use App\Data\BudgetPeriodStatusData;
use App\Enums\LedgerAccountType;
use App\Models\Budget;
use App\Models\BudgetPeriod;
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

        $this->incomeAccount = LedgerAccount::factory()
            ->for($this->user)
            ->ofType(LedgerAccountType::Income)
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
            ->state([
                'name' => 'Monthly Essentials',
            ])
            ->create();

        $this->budgetPeriod = BudgetPeriod::factory()
            ->for($this->budget)
            ->forPeriod('2025-11')
            ->state([
                'amount' => 1_100,
                'currency_code' => $this->currency->code,
            ])
            ->create();

        $this->foodCategory->update(['budget_id' => $this->budget->id]);
        $this->rentCategory->update(['budget_id' => $this->budget->id]);
    });

    it('summarizes budget status per period with transaction snapshots', function (): void {
        $novemberIncome = LedgerTransaction::factory()
            ->for($this->user)
            ->state([
                'budget_id' => $this->budget->id,
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

        LedgerEntry::factory()
            ->for($novemberIncome, 'transaction')
            ->for($this->incomeAccount, 'account')
            ->state([
                'amount' => -400,
                'currency_code' => $this->currency->code,
            ])
            ->create();

        $novemberRent = LedgerTransaction::factory()
            ->for($this->user)
            ->state([
                'budget_id' => $this->budget->id,
                'effective_at' => CarbonImmutable::parse('2025-11-10 12:00:00'),
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

        LedgerEntry::factory()
            ->for($novemberRent, 'transaction')
            ->for($this->assetAccount, 'account')
            ->state([
                'amount' => -600,
                'currency_code' => $this->currency->code,
            ])
            ->create();

        $decemberSpending = LedgerTransaction::factory()
            ->for($this->user)
            ->state([
                'budget_id' => $this->budget->id,
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

        expect($status)->toHaveCount(1);

        /** @var BudgetPeriodStatusData $summary */
        $summary = $status->first();

        expect($summary)->toBeInstanceOf(BudgetPeriodStatusData::class);
        expect($summary->budget_id)->toBe($this->budget->id);
        expect($summary->budgeted)->toBe('1100.000000');
        expect($summary->spent)->toBe('1000.000000');
        expect($summary->remaining)->toBe('100.000000');
    });

    it('adds a spent_amount subselect for eager loading aggregate data', function (): void {
        $novemberTransaction = LedgerTransaction::factory()
            ->for($this->user)
            ->state([
                'budget_id' => $this->budget->id,
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
        $this->service->addSpentAmountSubselect($query, '2025-11');

        $budgetWithAggregate = $query->first();

        expect(bccomp((string) $budgetWithAggregate->spent_amount, '500', 6))->toBe(0);
    });
});
