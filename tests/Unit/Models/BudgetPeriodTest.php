<?php

declare(strict_types=1);

use App\Models\Budget;
use App\Models\BudgetPeriod;
use App\Models\Currency;
use App\Models\User;

describe(BudgetPeriod::class, function (): void {
    it('belongs to a budget and currency', function (): void {
        $user = User::factory()->create();
        $budget = Budget::factory()->for($user)->create();
        $currency = Currency::factory()
            ->state([
                'code' => 'USD',
                'precision' => 2,
            ])
            ->create();

        $period = BudgetPeriod::factory()
            ->for($budget)
            ->forPeriod('2025-11')
            ->state([
                'currency_code' => $currency->code,
                'amount' => 750,
            ])
            ->create();

        expect($period->budget->is($budget))->toBeTrue();
        expect($period->currency->is($currency))->toBeTrue();
    });
});

