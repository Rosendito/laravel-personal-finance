<?php

declare(strict_types=1);

use App\Models\Budget;
use App\Models\BudgetPeriod;
use App\Models\User;

describe(BudgetPeriod::class, function (): void {
    it('belongs to a budget', function (): void {
        $user = User::factory()->create();
        $budget = Budget::factory()->for($user)->create();

        $period = BudgetPeriod::factory()
            ->for($budget)
            ->startingAt('2025-11-01', '2025-12-01')
            ->state([
                'amount' => 750,
            ])
            ->create();

        expect($period->budget->is($budget))->toBeTrue();
    });
});
