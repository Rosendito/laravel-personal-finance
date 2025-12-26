<?php

declare(strict_types=1);

use App\Models\Budget;
use App\Models\BudgetPeriod;
use App\Models\User;
use App\Services\Queries\BudgetPeriodForDateQueryService;
use Carbon\CarbonImmutable;

describe(BudgetPeriodForDateQueryService::class, function (): void {
    beforeEach(function (): void {
        $this->service = new BudgetPeriodForDateQueryService();
    });

    it('returns the period that contains the given date (end_at is treated as exclusive)', function (): void {
        $user = User::factory()->create();
        $budget = Budget::factory()->for($user)->create();

        $p1 = BudgetPeriod::factory()
            ->for($budget)
            ->startingAt('2025-11-15', '2025-12-15')
            ->create();

        $p2 = BudgetPeriod::factory()
            ->for($budget)
            ->startingAt('2025-12-15', '2026-01-15')
            ->create();

        $resolved = $this->service->forBudget($budget->id, CarbonImmutable::parse('2025-12-14'));

        expect($resolved)->not->toBeNull();
        expect($resolved?->is($p1))->toBeTrue();

        // 2025-12-15 is the boundary: should belong to p2
        $resolvedAtBoundary = $this->service->forBudget($budget->id, CarbonImmutable::parse('2025-12-15'));

        expect($resolvedAtBoundary)->not->toBeNull();
        expect($resolvedAtBoundary?->is($p2))->toBeTrue();
    });

    it('returns null when no period contains the date', function (): void {
        $user = User::factory()->create();
        $budget = Budget::factory()->for($user)->create();

        BudgetPeriod::factory()
            ->for($budget)
            ->startingAt('2025-11-15', '2025-12-15')
            ->create();

        $resolved = $this->service->forBudget($budget->id, CarbonImmutable::parse('2025-10-01'));

        expect($resolved)->toBeNull();
    });
});
