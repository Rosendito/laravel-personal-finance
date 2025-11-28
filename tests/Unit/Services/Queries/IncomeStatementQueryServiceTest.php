<?php

declare(strict_types=1);

use App\Data\IncomeStatementSummaryData;
use App\Enums\LedgerAccountType;
use App\Models\Currency;
use App\Models\LedgerAccount;
use App\Models\LedgerEntry;
use App\Models\LedgerTransaction;
use App\Models\User;
use App\Services\Queries\IncomeStatementQueryService;
use Carbon\CarbonImmutable;

describe(IncomeStatementQueryService::class, function (): void {
    beforeEach(function (): void {
        $this->service = new IncomeStatementQueryService();
        $this->user = User::factory()->create();
        $this->currency = Currency::query()->updateOrCreate(
            ['code' => 'USD'],
            ['precision' => 2],
        );

        $this->assetAccount = LedgerAccount::factory()
            ->for($this->user)
            ->ofType(LedgerAccountType::ASSET)
            ->state([
                'currency_code' => $this->currency->code,
            ])
            ->create();

        $this->incomeAccount = LedgerAccount::factory()
            ->for($this->user)
            ->ofType(LedgerAccountType::INCOME)
            ->state([
                'currency_code' => $this->currency->code,
            ])
            ->create();

        $this->expenseAccount = LedgerAccount::factory()
            ->for($this->user)
            ->ofType(LedgerAccountType::EXPENSE)
            ->state([
                'currency_code' => $this->currency->code,
            ])
            ->create();
    });

    it('computes totals for income and expenses within a period', function (): void {
        $janStart = CarbonImmutable::parse('2025-01-01 00:00:00');
        $janEnd = CarbonImmutable::parse('2025-01-31 23:59:59');

        $incomeTransaction = LedgerTransaction::factory()
            ->for($this->user)
            ->state([
                'effective_at' => CarbonImmutable::parse('2025-01-05 09:00:00'),
            ])
            ->create();

        LedgerEntry::factory()
            ->for($incomeTransaction, 'transaction')
            ->for($this->assetAccount, 'account')
            ->state([
                'amount' => 2_000,
                'currency_code' => $this->currency->code,
            ])
            ->create();

        LedgerEntry::factory()
            ->for($incomeTransaction, 'transaction')
            ->for($this->incomeAccount, 'account')
            ->state([
                'amount' => -2_000,
                'currency_code' => $this->currency->code,
            ])
            ->create();

        $expenseTransaction = LedgerTransaction::factory()
            ->for($this->user)
            ->state([
                'effective_at' => CarbonImmutable::parse('2025-01-20 12:00:00'),
            ])
            ->create();

        LedgerEntry::factory()
            ->for($expenseTransaction, 'transaction')
            ->for($this->assetAccount, 'account')
            ->state([
                'amount' => -500,
                'currency_code' => $this->currency->code,
            ])
            ->create();

        LedgerEntry::factory()
            ->for($expenseTransaction, 'transaction')
            ->for($this->expenseAccount, 'account')
            ->state([
                'amount' => 500,
                'currency_code' => $this->currency->code,
            ])
            ->create();

        $outOfPeriodTransaction = LedgerTransaction::factory()
            ->for($this->user)
            ->state([
                'effective_at' => CarbonImmutable::parse('2025-02-05 10:00:00'),
            ])
            ->create();

        LedgerEntry::factory()
            ->for($outOfPeriodTransaction, 'transaction')
            ->for($this->assetAccount, 'account')
            ->state([
                'amount' => 1_000,
                'currency_code' => $this->currency->code,
            ])
            ->create();

        LedgerEntry::factory()
            ->for($outOfPeriodTransaction, 'transaction')
            ->for($this->incomeAccount, 'account')
            ->state([
                'amount' => -1_000,
                'currency_code' => $this->currency->code,
            ])
            ->create();

        $totals = $this->service->summarize($this->user, $janStart, $janEnd);

        expect($totals)->toBeInstanceOf(IncomeStatementSummaryData::class);
        expect($totals->total_income)->toBe('2000.000000');
        expect($totals->total_expense)->toBe('500.000000');
        expect($totals->net_income)->toBe('1500.000000');
    });
});
