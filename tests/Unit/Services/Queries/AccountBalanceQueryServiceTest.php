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
    it('summarizes balances per account with optional as-of dates', function (): void {
        $service = new AccountBalanceQueryService();
        $user = User::factory()->create();
        $currency = Currency::query()->updateOrCreate(
            ['code' => 'USD'],
            ['precision' => 2],
        );

        $assetAccount = LedgerAccount::factory()
            ->for($user)
            ->ofType(LedgerAccountType::ASSET)
            ->state([
                'currency_code' => $currency->code,
            ])
            ->create();

        $incomeAccount = LedgerAccount::factory()
            ->for($user)
            ->ofType(LedgerAccountType::INCOME)
            ->state([
                'currency_code' => $currency->code,
            ])
            ->create();

        $expenseAccount = LedgerAccount::factory()
            ->for($user)
            ->ofType(LedgerAccountType::EXPENSE)
            ->state([
                'currency_code' => $currency->code,
            ])
            ->create();

        $earlyTransaction = LedgerTransaction::factory()
            ->for($user)
            ->state([
                'effective_at' => CarbonImmutable::parse('2025-11-05 08:00:00'),
                'posted_at' => '2025-11-06',
            ])
            ->create();

        LedgerEntry::factory()
            ->for($earlyTransaction, 'transaction')
            ->for($assetAccount, 'account')
            ->state([
                'amount' => 1_000,
                'currency_code' => $currency->code,
            ])
            ->create();

        LedgerEntry::factory()
            ->for($earlyTransaction, 'transaction')
            ->for($incomeAccount, 'account')
            ->state([
                'amount' => -1_000,
                'currency_code' => $currency->code,
            ])
            ->create();

        $lateTransaction = LedgerTransaction::factory()
            ->for($user)
            ->state([
                'effective_at' => CarbonImmutable::parse('2025-12-10 09:00:00'),
                'posted_at' => '2025-12-11',
            ])
            ->create();

        LedgerEntry::factory()
            ->for($lateTransaction, 'transaction')
            ->for($assetAccount, 'account')
            ->state([
                'amount' => -200,
                'currency_code' => $currency->code,
            ])
            ->create();

        LedgerEntry::factory()
            ->for($lateTransaction, 'transaction')
            ->for($expenseAccount, 'account')
            ->state([
                'amount' => 200,
                'currency_code' => $currency->code,
            ])
            ->create();

        $balancesAsOfNovember = $service->totalsForUser(
            $user,
            CarbonImmutable::parse('2025-11-30 23:59:59'),
        );

        $assetBalance = $balancesAsOfNovember->firstWhere('account_id', $assetAccount->id);
        $incomeBalance = $balancesAsOfNovember->firstWhere('account_id', $incomeAccount->id);
        $expenseBalance = $balancesAsOfNovember->firstWhere('account_id', $expenseAccount->id);

        expect($assetBalance)->toBeInstanceOf(AccountBalanceData::class);
        expect($assetBalance->balance)->toBe('1000.000000');
        expect($assetBalance->is_fundamental)->toBeFalse();

        expect($incomeBalance)->toBeInstanceOf(AccountBalanceData::class);
        expect($incomeBalance->balance)->toBe('-1000.000000');
        expect($incomeBalance->is_fundamental)->toBeFalse();

        expect($expenseBalance)->toBeInstanceOf(AccountBalanceData::class);
        expect($expenseBalance->balance)->toBe('0.000000');
        expect($expenseBalance->is_fundamental)->toBeFalse();

        $balancesAll = $service->totalsForUser($user);

        $assetBalanceAll = $balancesAll->firstWhere('account_id', $assetAccount->id);
        $expenseBalanceAll = $balancesAll->firstWhere('account_id', $expenseAccount->id);

        expect($assetBalanceAll)->toBeInstanceOf(AccountBalanceData::class);
        expect($assetBalanceAll->balance)->toBe('800.000000');
        expect($assetBalanceAll->is_fundamental)->toBeFalse();

        expect($expenseBalanceAll)->toBeInstanceOf(AccountBalanceData::class);
        expect($expenseBalanceAll->balance)->toBe('200.000000');
        expect($expenseBalanceAll->is_fundamental)->toBeFalse();
    });
});
