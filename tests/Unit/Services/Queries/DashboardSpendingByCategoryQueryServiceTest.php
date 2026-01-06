<?php

declare(strict_types=1);

use App\Enums\LedgerAccountType;
use App\Models\Category;
use App\Models\Currency;
use App\Models\LedgerAccount;
use App\Models\LedgerEntry;
use App\Models\LedgerTransaction;
use App\Models\User;
use App\Services\Queries\DashboardSpendingByCategoryQueryService;
use Carbon\CarbonImmutable;

describe(DashboardSpendingByCategoryQueryService::class, function (): void {
    it('returns totals grouped by category id (deterministic)', function (): void {
        $service = new DashboardSpendingByCategoryQueryService();
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

        $expenseAccount = LedgerAccount::factory()
            ->for($user)
            ->ofType(LedgerAccountType::EXPENSE)
            ->state([
                'currency_code' => $currency->code,
            ])
            ->create();

        $start = CarbonImmutable::parse('2025-01-01 00:00:00');
        $end = CarbonImmutable::parse('2025-01-31 23:59:59');

        $food = Category::factory()
            ->for($user)
            ->expense()
            ->state(['name' => 'Food'])
            ->create();

        $health = Category::factory()
            ->for($user)
            ->expense()
            ->state([
                'name' => 'Health',
                'is_reportable' => false,
            ])
            ->create();

        $t1 = LedgerTransaction::factory()
            ->for($user)
            ->withCategory($food)
            ->state(['effective_at' => CarbonImmutable::parse('2025-01-10 10:00:00')])
            ->create();

        LedgerEntry::factory()
            ->for($t1, 'transaction')
            ->for($assetAccount, 'account')
            ->state(['amount' => -100, 'currency_code' => $currency->code])
            ->create();

        LedgerEntry::factory()
            ->for($t1, 'transaction')
            ->for($expenseAccount, 'account')
            ->state(['amount' => 100, 'currency_code' => $currency->code])
            ->create();

        $t2 = LedgerTransaction::factory()
            ->for($user)
            ->withCategory($food)
            ->state(['effective_at' => CarbonImmutable::parse('2025-01-15 10:00:00')])
            ->create();

        LedgerEntry::factory()
            ->for($t2, 'transaction')
            ->for($assetAccount, 'account')
            ->state(['amount' => -200, 'currency_code' => $currency->code])
            ->create();

        LedgerEntry::factory()
            ->for($t2, 'transaction')
            ->for($expenseAccount, 'account')
            ->state(['amount' => 200, 'currency_code' => $currency->code])
            ->create();

        $t3 = LedgerTransaction::factory()
            ->for($user)
            ->withCategory($health)
            ->state(['effective_at' => CarbonImmutable::parse('2025-01-20 10:00:00')])
            ->create();

        LedgerEntry::factory()
            ->for($t3, 'transaction')
            ->for($assetAccount, 'account')
            ->state(['amount' => -50, 'currency_code' => $currency->code])
            ->create();

        LedgerEntry::factory()
            ->for($t3, 'transaction')
            ->for($expenseAccount, 'account')
            ->state(['amount' => 50, 'currency_code' => $currency->code])
            ->create();

        $t4 = LedgerTransaction::factory()
            ->for($user)
            ->state(['effective_at' => CarbonImmutable::parse('2025-01-25 10:00:00')])
            ->create();

        LedgerEntry::factory()
            ->for($t4, 'transaction')
            ->for($assetAccount, 'account')
            ->state(['amount' => -30, 'currency_code' => $currency->code])
            ->create();

        LedgerEntry::factory()
            ->for($t4, 'transaction')
            ->for($expenseAccount, 'account')
            ->state(['amount' => 30, 'currency_code' => $currency->code])
            ->create();

        $totals = $service->totals($user, $start, $end)->values();

        expect($totals)->toHaveCount(2);

        expect($totals[0]->categoryId)->toBe($food->id);
        expect($totals[0]->name)->toBe('Food');
        expect((float) $totals[0]->total)->toBe(300.0);

        expect($totals[1]->categoryId)->toBeNull();
        expect($totals[1]->name)->toBe('Sin categoría');
        expect((float) $totals[1]->total)->toBe(30.0);
    });
});
