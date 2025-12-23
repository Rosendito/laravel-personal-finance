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
    beforeEach(function (): void {
        $this->service = new DashboardSpendingByCategoryQueryService();
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

        $this->expenseAccount = LedgerAccount::factory()
            ->for($this->user)
            ->ofType(LedgerAccountType::EXPENSE)
            ->state([
                'currency_code' => $this->currency->code,
            ])
            ->create();
    });

    it('returns totals grouped by category id (deterministic)', function (): void {
        $start = CarbonImmutable::parse('2025-01-01 00:00:00');
        $end = CarbonImmutable::parse('2025-01-31 23:59:59');

        $food = Category::factory()
            ->for($this->user)
            ->expense()
            ->state(['name' => 'Food'])
            ->create();

        $health = Category::factory()
            ->for($this->user)
            ->expense()
            ->state(['name' => 'Health'])
            ->create();

        $t1 = LedgerTransaction::factory()
            ->for($this->user)
            ->withCategory($food)
            ->state(['effective_at' => CarbonImmutable::parse('2025-01-10 10:00:00')])
            ->create();

        LedgerEntry::factory()
            ->for($t1, 'transaction')
            ->for($this->assetAccount, 'account')
            ->state(['amount' => -100, 'currency_code' => $this->currency->code])
            ->create();

        LedgerEntry::factory()
            ->for($t1, 'transaction')
            ->for($this->expenseAccount, 'account')
            ->state(['amount' => 100, 'currency_code' => $this->currency->code])
            ->create();

        $t2 = LedgerTransaction::factory()
            ->for($this->user)
            ->withCategory($food)
            ->state(['effective_at' => CarbonImmutable::parse('2025-01-15 10:00:00')])
            ->create();

        LedgerEntry::factory()
            ->for($t2, 'transaction')
            ->for($this->assetAccount, 'account')
            ->state(['amount' => -200, 'currency_code' => $this->currency->code])
            ->create();

        LedgerEntry::factory()
            ->for($t2, 'transaction')
            ->for($this->expenseAccount, 'account')
            ->state(['amount' => 200, 'currency_code' => $this->currency->code])
            ->create();

        $t3 = LedgerTransaction::factory()
            ->for($this->user)
            ->withCategory($health)
            ->state(['effective_at' => CarbonImmutable::parse('2025-01-20 10:00:00')])
            ->create();

        LedgerEntry::factory()
            ->for($t3, 'transaction')
            ->for($this->assetAccount, 'account')
            ->state(['amount' => -50, 'currency_code' => $this->currency->code])
            ->create();

        LedgerEntry::factory()
            ->for($t3, 'transaction')
            ->for($this->expenseAccount, 'account')
            ->state(['amount' => 50, 'currency_code' => $this->currency->code])
            ->create();

        $t4 = LedgerTransaction::factory()
            ->for($this->user)
            ->state(['effective_at' => CarbonImmutable::parse('2025-01-25 10:00:00')])
            ->create();

        LedgerEntry::factory()
            ->for($t4, 'transaction')
            ->for($this->assetAccount, 'account')
            ->state(['amount' => -30, 'currency_code' => $this->currency->code])
            ->create();

        LedgerEntry::factory()
            ->for($t4, 'transaction')
            ->for($this->expenseAccount, 'account')
            ->state(['amount' => 30, 'currency_code' => $this->currency->code])
            ->create();

        $totals = $this->service->totals($this->user, $start, $end)->values();

        expect($totals)->toHaveCount(3);

        expect($totals[0]->categoryId)->toBe($food->id);
        expect($totals[0]->name)->toBe('Food');
        expect((float) $totals[0]->total)->toBe(300.0);

        expect($totals[1]->categoryId)->toBe($health->id);
        expect($totals[1]->name)->toBe('Health');
        expect((float) $totals[1]->total)->toBe(50.0);

        expect($totals[2]->categoryId)->toBeNull();
        expect($totals[2]->name)->toBe('Sin categoría');
        expect((float) $totals[2]->total)->toBe(30.0);
    });
});
