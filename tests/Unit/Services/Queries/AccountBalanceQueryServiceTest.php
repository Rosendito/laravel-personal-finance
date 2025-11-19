<?php

declare(strict_types=1);

use App\Data\AccountBalanceData;
use App\Enums\LedgerAccountType;
use App\Models\Currency;
use App\Models\LedgerAccount;
use App\Models\LedgerEntry;
use App\Models\LedgerTransaction;
use App\Models\User;
use App\Services\Queries\AccountBalanceQueryService;
use Carbon\CarbonImmutable;

describe(AccountBalanceQueryService::class, function (): void {
    beforeEach(function (): void {
        $this->service = new AccountBalanceQueryService();
        $this->user = User::factory()->create();
        $this->currency = Currency::query()->updateOrCreate(
            ['code' => 'USD'],
            ['precision' => 2],
        );

        $this->assetAccount = LedgerAccount::factory()
            ->for($this->user)
            ->ofType(LedgerAccountType::Asset)
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

        $this->expenseAccount = LedgerAccount::factory()
            ->for($this->user)
            ->ofType(LedgerAccountType::Expense)
            ->state([
                'currency_code' => $this->currency->code,
            ])
            ->create();
    });

    it('summarizes balances per account with optional as-of dates', function (): void {
        $earlyTransaction = LedgerTransaction::factory()
            ->for($this->user)
            ->state([
                'effective_at' => CarbonImmutable::parse('2025-11-05 08:00:00'),
                'posted_at' => '2025-11-06',
            ])
            ->create();

        LedgerEntry::factory()
            ->for($earlyTransaction, 'transaction')
            ->for($this->assetAccount, 'account')
            ->state([
                'amount' => 1_000,
                'currency_code' => $this->currency->code,
            ])
            ->create();

        LedgerEntry::factory()
            ->for($earlyTransaction, 'transaction')
            ->for($this->incomeAccount, 'account')
            ->state([
                'amount' => -1_000,
                'currency_code' => $this->currency->code,
            ])
            ->create();

        $lateTransaction = LedgerTransaction::factory()
            ->for($this->user)
            ->state([
                'effective_at' => CarbonImmutable::parse('2025-12-10 09:00:00'),
                'posted_at' => '2025-12-11',
            ])
            ->create();

        LedgerEntry::factory()
            ->for($lateTransaction, 'transaction')
            ->for($this->assetAccount, 'account')
            ->state([
                'amount' => -200,
                'currency_code' => $this->currency->code,
            ])
            ->create();

        LedgerEntry::factory()
            ->for($lateTransaction, 'transaction')
            ->for($this->expenseAccount, 'account')
            ->state([
                'amount' => 200,
                'currency_code' => $this->currency->code,
            ])
            ->create();

        $balancesAsOfNovember = $this->service->totalsForUser(
            $this->user,
            CarbonImmutable::parse('2025-11-30 23:59:59'),
        );

        $assetBalance = $balancesAsOfNovember->firstWhere('account_id', $this->assetAccount->id);
        $incomeBalance = $balancesAsOfNovember->firstWhere('account_id', $this->incomeAccount->id);
        $expenseBalance = $balancesAsOfNovember->firstWhere('account_id', $this->expenseAccount->id);

        expect($assetBalance)->toBeInstanceOf(AccountBalanceData::class);
        expect($assetBalance->balance)->toBe('1000.000000');
        expect($assetBalance->is_fundamental)->toBeFalse();

        expect($incomeBalance)->toBeInstanceOf(AccountBalanceData::class);
        expect($incomeBalance->balance)->toBe('-1000.000000');
        expect($incomeBalance->is_fundamental)->toBeFalse();

        expect($expenseBalance)->toBeInstanceOf(AccountBalanceData::class);
        expect($expenseBalance->balance)->toBe('0.000000');
        expect($expenseBalance->is_fundamental)->toBeFalse();

        $balancesAll = $this->service->totalsForUser($this->user);

        $assetBalanceAll = $balancesAll->firstWhere('account_id', $this->assetAccount->id);
        $expenseBalanceAll = $balancesAll->firstWhere('account_id', $this->expenseAccount->id);

        expect($assetBalanceAll)->toBeInstanceOf(AccountBalanceData::class);
        expect($assetBalanceAll->balance)->toBe('800.000000');
        expect($assetBalanceAll->is_fundamental)->toBeFalse();

        expect($expenseBalanceAll)->toBeInstanceOf(AccountBalanceData::class);
        expect($expenseBalanceAll->balance)->toBe('200.000000');
        expect($expenseBalanceAll->is_fundamental)->toBeFalse();
    });
});
